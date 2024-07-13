<?php

namespace Guuspi17\ClassManifest\Tests\TestFiles;

abstract class AbstractTest2Class
{
    /**
     * @inheritdoc
     */
    public function getParent(): ?string
    {
        if (null === $this->parent) {
            $this->parent = 'test';
        }

        return $this->parent;
    }
}