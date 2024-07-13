<?php

namespace Guuspi17\ClassManifest\Tests\TestFiles;

use BaseParser;
use BaseParser\CheckList;

/**
 * @property int $id
 * @property string|null $question
 * @property string|null $answer
 * @property int|null $sort
 * @property string $date_create [datetime]
 * @property string $date_update [datetime]
 * @property int $id_editor [bigint(20) unsigned]
 */
interface Test1Interface extends Test2Interface
{
    /**
     * Вернуть настройку аттрибутов для отображения в истории
     */
    public static function getAttributesSettingForHistoryView(): array;

    /**
     * Вернуть id родительской сущности для истории
     * Например: у auction_item - это id аукциона. У auction - это его же id.
     * @return string
     */
    public function getParentIdForHistory(): string;

    /**
     * Вернуть карту соответствия названия события record к событию логирования
     * @return array
     */
    public static function getMapNameRecordEventAndLogEvent(): array;

    /**
     * Данная сущность считается ли удаленной для истории
     * @param array $oldAttributes;
     * @return bool
     */
    public function isDeletedForHistory(array $oldAttributes): bool;
}
