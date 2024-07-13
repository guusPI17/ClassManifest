<?php

namespace Guuspi17\ClassManifest\Tests\TestFiles;

interface Test2Interface
{
    /**
     * Вызвать событие
     * @param int $event
     */
    public function triggerEvent(int $event, $user = null);
}
