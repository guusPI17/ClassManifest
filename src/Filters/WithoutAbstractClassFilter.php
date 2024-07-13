<?php

namespace Guuspi17\ClassManifest\Filters;

class WithoutAbstractClassFilter implements FilterInterface
{
    /**
     * Список всех абстрактных классов
     */
    protected array $abstractClasses = [];

    /**
     * @param array $abstractClasses
     */
    public function __construct(array $abstractClasses)
    {
        $this->abstractClasses = $abstractClasses;
    }

    /**
     * @inheritDoc
     */
    public static function getCode(): string
    {
        return (new \ReflectionClass(static::class))->getShortName();
    }

    /**
     * @inheritDoc
     */
    public function getFilteredClasses(array $classes, $params): array
    {
        if (!is_bool($params) || !$params) {
            return [];
        }

        $notAbstractClasses = [];
        foreach ($classes as $key => $class) {
            if (!isset($this->abstractClasses[$class])) {
                $notAbstractClasses[$key] = $class;
            }
        }

        return $notAbstractClasses;
    }
}