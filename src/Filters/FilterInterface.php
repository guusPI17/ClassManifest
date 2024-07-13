<?php

namespace Guuspi17\ClassManifest\Filters;

interface FilterInterface
{
    /**
     * Получить код фильтра
     * @return string
     */
    public static function getCode(): string;

    /**
     * Получить отфильтрованные классы
     * @param array $classes
     * @param mixed $params
     * @return array
     */
    public function getFilteredClasses(array $classes, $params): array;
}