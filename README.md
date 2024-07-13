# Class manifest

## Описание

Данная библиотека позволяет получить список всех классов/интерфейсов в указанных директориях с дальнейшей возможностью их фильтрации и сохранением в кэш.  
**Первичное предназначение - получение всех классов, которые реализуют указанный интерфейс.**

## Установка:
```
composer require guuspi17/class-manifest
```

## Функционал:
* Нахождение классов и интерфейсов в указанных директориях;
* Нахождение классов, которые расширяют указанный (extends) класс;
* Нахождение интерфейсов, которых расширяют указанный (extends) интерфейс;
* Нахождение классов, которые реализуют указанный (implements) интерфейс;
* Фильтрация по абстрактным классам;
* Сохранение данных в кэш (интерфейс Psr/CacheInterface).
## Тесты:
```
composer test
```

## PHPCS:
```
composer cs
```

## Базовое использование:

```php
$classManifest = new \Guuspi17\ClassManifest\ClassManifest();
$classManifest->setDirForSearch(['/app/phpFiles']); // указываем директории, в которых будет происходить поиск *.php файлов.
$classManifest->setCache($fileCache); // Опционально, где $fileCache - объект кеша, реализующий Psr/CacheInterface.
$classManifest->init();

// Вывести классы, которые реализуют интерфейс NotificationInterface::class.
var_dump($classManifest->getInterfaceImplementors(NotificationInterface::class));

// Вывести классы, которые реализуют интерфейс NotificationInterface::class, исключая абстрактные классы.
var_dump($classManifest->getInterfaceImplementors(
    NotificationInterface::class, 
    [\Guuspi17\ClassManifest\Filters\WithoutAbstractClassFilter::getCode() => true]
));
```

