<?php

namespace Guuspi17\ClassManifest;

use PhpParser\NodeVisitor;

/**
 * Интерфейс посетителя, который группирует начальную информацию после обработки файла парсером
 */
interface ClassManifestNodeVisitorInterface extends NodeVisitor
{
    /**
     * Сбросить состояние
     * @return void
     */
    public function resetState(): void;

    /**
     * Вернуть классы
     * @return array
     */
    public function getClasses(): array;

    /**
     * Вернуть интерфейсы
     * @return array
     */
    public function getInterfaces(): array;
}