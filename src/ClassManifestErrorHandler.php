<?php

namespace Guuspi17\ClassManifest;

use PhpParser\Error;
use PhpParser\ErrorHandler;

/**
 * Обработчик ошибок для парсера
 */
class ClassManifestErrorHandler implements ErrorHandler
{
    protected string $pathname;

    /**
     * @param string $pathname
     */
    public function __construct(string $pathname)
    {
        $this->pathname = $pathname;
    }

    /**
     * @inheritdoc
     */
    public function handleError(Error $error): void
    {
        $newMessage = sprintf('%s in %s', $error->getRawMessage(), $this->pathname);
        $error->setRawMessage($newMessage);

        throw $error;
    }
}