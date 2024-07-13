<?php

namespace Guuspi17\ClassManifest\Tests\TestFiles;

abstract class AbstractTest1Class extends AbstractTest2Class implements Test2Interface
{
    final public function __construct($target, $targetId)
    {
        $this->target = $target;
        $this->targetId = $targetId;
    }

    /**
     * @inheritdoc
     */
    final public function getParent(): ?string
    {
        if (null === $this->parent) {
            $this->parent = 'test';
        }

        return $this->parent;
    }
}