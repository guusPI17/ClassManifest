<?php

namespace Guuspi17\ClassManifest\Tests\TestFiles;

/**
 * Тестовый класс
 * @property int $id
 * @property string|null $question
 * @property string|null $answer
 */
class Test0Class implements Test0Interface
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'test0';
    }

    public function triggerEvent(int $event, $user = null)
    {
        return '';
    }

    public function isDeletedForHistory(array $oldAttributes): bool
    {
        return true;
    }
}
