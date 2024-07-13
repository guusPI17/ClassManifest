<?php

namespace Guuspi17\ClassManifest\Tests\TestFiles;

/**
 * @property int $id
 * @property string|null $question
 * @property string|null $answer
 */
interface Test0Interface extends Test2Interface
{
    /**
     * Данная сущность считается ли удаленной для истории
     * @param array $oldAttributes;
     * @return bool
     */
    public function isDeletedForHistory(array $oldAttributes): bool;
}
