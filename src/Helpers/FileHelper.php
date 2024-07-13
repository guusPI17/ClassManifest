<?php

namespace Guuspi17\ClassManifest\Helpers;

class FileHelper
{
    /**
     * Переданный путь ссылается ли на каталог
     * @param string $dirPath
     * @return bool
     */
    public static function isDir(string $dirPath): bool
    {
        return file_exists($dirPath) && is_dir($dirPath);
    }

    /**
     * Найти файлы
     * @param string $dir
     * @param string $mask
     * @return array
     */
    public static function findFiles(string $dir, string $mask): array
    {
        $files = glob($dir . DIRECTORY_SEPARATOR . $mask, GLOB_NOSORT);
        if (false === $files) {
            throw new \RuntimeException("Unable to open directory: $dir");
        }

        foreach ($files as $key => $file) {
            if (static::isDir($file)) {
                unset($files[$key]);
            }
        }

        return $files;
    }
}