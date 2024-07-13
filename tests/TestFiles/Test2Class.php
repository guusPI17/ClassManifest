<?php

namespace Guuspi17\ClassManifest\Tests\TestFiles;

/**
 * Тестовый класс
 * @property int $id
 * @property string|null $question
 * @property string|null $answer
 * @property int|null $sort
 * @property string $date_create [datetime]
 * @property string $date_update [datetime]
 * @property int $id_editor [bigint(20) unsigned]
 */
class Test2Class extends AbstractTest2Class implements Test1Interface
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'test2';
    }

    public static function getAttributesSettingForHistoryView(): array
    {
        return [];
    }

    public function getParentIdForHistory(): string
    {
        return '';
    }

    public static function getMapNameRecordEventAndLogEvent(): array
    {
        return [];
    }

    public function isDeletedForHistory(array $oldAttributes): bool
    {
        return false;
    }

    public function triggerEvent(int $event, $user = null)
    {
        return '';
    }
}
