<?php

namespace Guuspi17\ClassManifest;

use Guuspi17\ClassManifest\Filters\FilterInterface;
use Guuspi17\ClassManifest\Filters\WithoutAbstractClassFilter;
use Guuspi17\ClassManifest\Helpers\FileHelper;
use PhpParser\NodeTraverser;
use PhpParser\NodeTraverserInterface;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Компонент, который создает манифест всех классов, интерфейсов и кэширует его.
 */
class ClassManifest
{
    /**
     * Ключ кеша по умолчанию
     * @var string
     */
    protected const DEFAULT_CACHE_KEY = 'class_manifest';

    /**
     * Свойство кеша файла - классы
     * @var string
     */
    protected const CLASSES_FILE_CACHE_PROPERTY = 'classes';

    /**
     * Свойство кеша файла - интерфейсы
     * @var string
     */
    protected const INTERFACES_FILE_CACHE_PROPERTY = 'interfaces';

    /**
     * Свойства данных кеша по файлу
     * @var array
     */
    protected const FILE_CACHE_PROPERTIES = [
        self::CLASSES_FILE_CACHE_PROPERTY,
        self::INTERFACES_FILE_CACHE_PROPERTY,
    ];

    /**
     * Директории для поиска, по которым можно создать манифест
     */
    protected array $dirForSearch = [];

    /**
     * Ключ для использования в кэше
     */
    protected ?string $cacheKey = null;

    /**
     * Массив свойств для кэширования, которые определены в данном классе
     */
    protected array $serialisedProperties = [
        'abstractClasses',
        'classes',
        'interfaces',
        'classesExtenders',
        'interfacesExtenders',
        'implementors',
        'dataByFilesHashes',
    ];

    /**
     * Список абстрактных классов
     */
    protected array $abstractClasses = [];

    /**
     * Список классов
     */
    protected array $classes = [];

    /**
     * Список интерфейсов
     */
    protected array $interfaces = [];

    /**
     * Список расширителей классов
     */
    protected array $classesExtenders = [];

    /**
     * Список расширителей интерфейсов
     */
    protected array $interfacesExtenders = [];

    /**
     * Список разработчиков интерфейсов
     */
    protected array $implementors = [];

    /**
     * Список корневых классов
     */
    protected array $rootClasses = [];

    /**
     * Список корневых интерфейсов
     */
    protected array $rootInterfaces = [];

    /**
     * Список прямых потомков для классов
     */
    protected array $childClasses = [];

    /**
     * Список прямых потомков для интерфейсов
     */
    protected array $childInterfaces = [];

    /**
     * Данные по хэшу файлов
     */
    protected array $dataByFilesHashes = [];

    protected ?Parser $parser = null;

    protected ?NodeTraverserInterface $nodeTraverser = null;

    protected ?ClassManifestNodeNodeVisitor $nodeVisitor = null;

    protected ?CacheInterface $cache = null;

    /**
     * @var FilterInterface[]
     */
    protected array $filters = [];

    /**
     * Инициализировать манифест класса
     * @throws InvalidArgumentException
     */
    public function init(): void
    {
        if (
            $this->getCache()
            && ($data = $this->getCache()->get($this->getCacheKey()))
            && $this->loadState($data)
        ) {
            return;
        }

        $this->regenerate();

        $this->initFilters();
    }

    /**
     * Инициализировать фильтры
     * @return void
     */
    protected function initFilters(): void
    {
        $this->addFilter(new WithoutAbstractClassFilter($this->abstractClasses));
    }

    /**
     * Регенерировать файл манифеста
     * @return void
     * @throws InvalidArgumentException
     */
    protected function regenerate(): void
    {
        $this->loadState([]);
        $this->rootClasses = [];
        $this->childClasses = [];
        $this->rootInterfaces = [];
        $this->childInterfaces = [];

        foreach ($this->dirForSearch as $dirPath) {
            if (!FileHelper::isDir($dirPath)) {
                continue;
            }

            $filesPaths = FileHelper::findFiles($dirPath, '*.php');
            foreach ($filesPaths as $filePath) {
                $this->handleFile($filePath);
            }
        }

        foreach ($this->rootClasses as $root) {
            $this->addClassesExtendersByRootClass($root);
        }

        foreach ($this->rootInterfaces as $root) {
            $this->addInterfacesExtendersByRootInterface($root);
        }

        foreach ($this->interfaces as $interface => $path) {
            $this->addImplementorsByInterface($interface);
        }

        if ($this->getCache()) {
            $this->getCache()->set($this->getCacheKey(), $this->getState());
        }
    }

    /**
     * Обработать файл
     * @param string $filePath
     * @return void
     */
    protected function handleFile(string $filePath): void
    {
        $hash = $this->getFileHash($filePath);

        if (
            isset($this->dataByFilesHashes[$hash])
            && ($item = $this->dataByFilesHashes[$hash])
            && $this->validateFileHashData($item)
        ) {
            $classes = $item[static::CLASSES_FILE_CACHE_PROPERTY];
            $interfaces = $item[static::INTERFACES_FILE_CACHE_PROPERTY];
        } else {
            $fileContents = ClassContentRemover::removeContent($filePath);
            $errorHandler = new ClassManifestErrorHandler($filePath);
            try {
                $stmts = $this->getParser()->parse($fileContents, $errorHandler);
            } catch (\PhpParser\Error $e) {
                // если наше искаженное содержимое сломается, то повторяем попытку с оригинальным файлом
                $stmts = $this->getParser()->parse(file_get_contents($filePath), $errorHandler);
            }
            $this->getNodeTraverser()->traverse($stmts);

            $classes = $this->getNodeVisitor()->getClasses();
            $interfaces = $this->getNodeVisitor()->getInterfaces();

            $this->dataByFilesHashes[$hash] = [
                static::CLASSES_FILE_CACHE_PROPERTY => $classes,
                static::INTERFACES_FILE_CACHE_PROPERTY => $interfaces,
            ];
        }

        $handleInfo = static function (array $info, string $name, array &$roots, array &$children) {
            if (empty($info[ClassManifestNodeNodeVisitor::KEY_EXTENDS])) {
                $roots[$name] = $name;
            } else {
                foreach ($info[ClassManifestNodeNodeVisitor::KEY_EXTENDS] as $ancestor) {
                    $children[$ancestor][$name] = $name;
                }
            }
        };

        // обрабатываем классы после анализатора
        foreach ($classes as $className => $classInfo) {
            $this->classes[$className] = $className;

            if (
                isset($classInfo[ClassManifestNodeNodeVisitor::KEY_IS_ABSTRACT])
                && $classInfo[ClassManifestNodeNodeVisitor::KEY_IS_ABSTRACT]
            ) {
                $this->abstractClasses[$className] = $className;
            }

            $handleInfo($classInfo, $className, $this->rootClasses, $this->childClasses);

            foreach ($classInfo[ClassManifestNodeNodeVisitor::KEY_INTERFACES] as $interface) {
                $this->implementors[$interface][$className] = $className;
            }
        }

        // обрабатываем интерфейсы после анализатора
        foreach ($interfaces as $interfaceName => $interfaceInfo) {
            $this->interfaces[$interfaceName] = $interfaceName;

            $handleInfo($interfaceInfo, $interfaceName, $this->rootInterfaces, $this->childInterfaces);
        }
    }

    /**
     * Получить хэш файла
     * @param string $filePath
     * @return string
     */
    protected function getFileHash(string $filePath): string
    {
        // все недопустимые символы заменяем на _
        return preg_replace('/[^a-zA-Z0-9_]/', '_', $filePath)
            . '_'
            . md5_file($filePath)
        ;
    }

    /**
     * Добавить информацию о разработчиках интерфейсов по указанному интерфейсу
     * @param string $interface
     * @return array
     */
    protected function addImplementorsByInterface(string $interface): array
    {
        foreach ($this->implementors[$interface] ?? [] as $class) {
            $this->implementors[$interface] = array_merge(
                $this->implementors[$interface],
                $this->getClassExtenders($class)
            );
        }
        foreach ($this->interfacesExtenders[$interface] ?? [] as $extender) {
            $this->implementors[$interface] = array_merge(
                $this->implementors[$interface] ?? [],
                $this->addImplementorsByInterface($extender)
            );
        }

        return $this->implementors[$interface] ?? [];
    }

    /**
     * Добавить информацию о расширителях классов по указанному корневому классу
     * @param string $rootClass
     * @return array
     */
    protected function addClassesExtendersByRootClass(string $rootClass): array
    {
        $this->mergeInterfacesExtendersOrClassesExtenders(
            $this->classesExtenders,
            $this->childClasses,
            $rootClass
        );

        return $this->classesExtenders[$rootClass] ?? [];
    }

    /**
     * Добавить информацию о расширителях интерфейсов по указанному корневому классу
     * @param string $rootInterface
     * @return array
     */
    protected function addInterfacesExtendersByRootInterface(string $rootInterface): array
    {
        $this->mergeInterfacesExtendersOrClassesExtenders(
            $this->interfacesExtenders,
            $this->childInterfaces,
            $rootInterface
        );

        return $this->interfacesExtenders[$rootInterface] ?? [];
    }

    /**
     * Объединить информацию о расширителях интерфейсов или расширителях классов
     * @param array $extenders
     * @param array $children
     * @param string $class
     * @return array
     */
    private function mergeInterfacesExtendersOrClassesExtenders(array &$extenders, array $children, string $class): array
    {
        if (empty($children[$class])) {
            return [];
        }

        $extenders[$class] = $children[$class];
        foreach ($children[$class] as $childClass) {
            $extenders[$class] = array_merge(
                $extenders[$class],
                $this->mergeInterfacesExtendersOrClassesExtenders($extenders, $children, $childClass)
            );
        }

        return $extenders[$class];
    }

    /**
     * Проверить данные хэш файла
     * @param mixed $data
     * @return bool
     */
    protected function validateFileHashData($data): bool
    {
        if (!$data || !is_array($data)) {
            return false;
        }
        foreach (static::FILE_CACHE_PROPERTIES as $key) {
            if (!isset($data[$key]) || !is_array($data[$key])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Установить директории для поиска php файлов
     * @param array $dirForSearch
     * @return $this
     */
    public function setDirForSearch(array $dirForSearch): self
    {
        $this->dirForSearch = $dirForSearch;

        return $this;
    }

    /**
     * Загрузить состояние
     * @param array $data
     * @return bool
     */
    protected function loadState(array $data): bool
    {
        $success = true;
        foreach ($this->serialisedProperties as $property) {
            if (isset($data[$property]) && is_array($data[$property])) {
                $value = $data[$property];
            } else {
                $success = false;
                $value = [];
            }
            $this->$property = $value;
        }

        return $success;
    }

    /**
     * Вернуть текущее состояние
     * @return array
     */
    protected function getState(): array
    {
        $data = [];
        foreach ($this->serialisedProperties as $property) {
            $data[$property] = $this->$property;
        }

        return $data;
    }

    /**
     * Получить парсер
     * @return Parser
     */
    public function getParser(): Parser
    {
        if (!$this->parser) {
            $this->parser = (new ParserFactory())->createForNewestSupportedVersion();
        }

        return $this->parser;
    }

    /**
     * Установить парсер
     * @param Parser $parser
     * @return $this
     */
    public function setParser(Parser $parser): self
    {
        $this->parser = $parser;

        return $this;
    }

    /**
     * Получить преобразователь узла
     * @return NodeTraverser
     */
    public function getNodeTraverser(): NodeTraverserInterface
    {
        if (!$this->nodeTraverser) {
            $this->nodeTraverser = new NodeTraverser();
            $this->nodeTraverser->addVisitor(new NameResolver());
            $this->nodeTraverser->addVisitor($this->getNodeVisitor());
        }

        return $this->nodeTraverser;
    }

    /**
     * Установить преобразователь узла
     * @param NodeTraverserInterface $nodeTraverser
     * @return $this
     */
    public function setNodeTraverser(NodeTraverserInterface $nodeTraverser): self
    {
        $this->nodeTraverser = $nodeTraverser;

        return $this;
    }

    /**
     * Получить посетителя узла
     * @return ClassManifestNodeNodeVisitor
     */
    public function getNodeVisitor(): ClassManifestNodeNodeVisitor
    {
        if (!$this->nodeVisitor) {
            $this->nodeVisitor = new ClassManifestNodeNodeVisitor();
        }

        return $this->nodeVisitor;
    }

    /**
     * Установить посетителя узла
     * @param ClassManifestNodeVisitorInterface $visitor
     * @return $this
     */
    public function setNodeVisitor(ClassManifestNodeVisitorInterface $visitor): self
    {
        $this->nodeVisitor = $visitor;

        return $this;
    }

    /**
     * Получить кэш
     * @return CacheInterface|null
     */
    public function getCache(): ?CacheInterface
    {
        return $this->cache;
    }

    /**
     * Установить кэш
     * @param CacheInterface|null $cache
     * @return $this
     */
    public function setCache(?CacheInterface $cache): self
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * Получить ключ кеша
     * @return string
     */
    public function getCacheKey(): string
    {
        if (null === $this->cacheKey || '' === trim($this->cacheKey)) {
            return static::DEFAULT_CACHE_KEY;
        }

        return $this->cacheKey;
    }

    /**
     * Установить ключ кеша
     * @param string $key
     * @return $this
     */
    public function setCacheKey(string $key): self
    {
        $this->cacheKey = $key;

        return $this;
    }

    /**
     * Добавить фильтр
     * @param FilterInterface $filter
     * @return $this
     */
    public function addFilter(FilterInterface $filter): self
    {
        $this->filters[$filter::getCode()] = $filter;

        return $this;
    }

    /**
     * Удалить фильтр
     * @param string $code
     * @return $this
     */
    public function removeFilter(string $code): self
    {
        unset($this->filters[$code]);

        return $this;
    }

    /**
     * Очистить фильтры
     * @return $this
     */
    public function clearFilters(): self
    {
        $this->filters = [];

        return $this;
    }

    /**
     * Получить список классов
     * @param array $filters
     * @return array
     */
    public function getClasses(array $filters = []): array
    {
        return $this->getFilteredClasses($this->classes, $filters);
    }

    /**
     * Получить список интерфейсов
     * @param array $filters
     * @return array
     */
    public function getInterfaces(array $filters = []): array
    {
        return $this->getFilteredClasses($this->interfaces, $filters);
    }

    /**
     * Получить расширителей класса (прямых и косвенных)
     * @param string|object $class
     * @param array $filters
     * @return array
     */
    public function getClassExtenders($class, array $filters = []): array
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        return $this->getFilteredClasses($this->classesExtenders[$class] ?? [], $filters);
    }

    /**
     * Получить расширителей интерфейса (прямых и косвенных)
     * @param string $interface
     * @param array $filters
     * @return array
     */
    public function getInterfaceExtenders(string $interface, array $filters = []): array
    {
        return $this->getFilteredClasses($this->interfacesExtenders[$interface] ?? [], $filters);
    }

    /**
     * Получить массив разработчиков интерфейса (прямых и косвенных)
     * @param string $interface
     * @param array $filters
     * @return array
     */
    public function getInterfaceImplementors(string $interface, array $filters = []): array
    {
        return $this->getFilteredClasses($this->implementors[$interface] ?? [], $filters);
    }

    /**
     * Получить отфильтрованные классы
     * @param array $classes
     * @param array $filters
     * @return array
     */
    protected function getFilteredClasses(array $classes, array $filters): array
    {
        foreach ($filters as $code => $params) {
            if (isset($this->filters[$code]) && $this->filters[$code] instanceof FilterInterface) {
                $classes = $this->filters[$code]->getFilteredClasses($classes, $params);
            }
        }

        return $classes;
    }
}