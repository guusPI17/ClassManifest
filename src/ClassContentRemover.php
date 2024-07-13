<?php

namespace Guuspi17\ClassManifest;

/**
 * Класс для очистки содержимого PHP-файла, содержащего классы/интерфейсы.
 * Он удаляет любой код, содержащий внутри фигурных скобок
 */
class ClassContentRemover
{
    /**
     * Удалить контент в переданном файле и вернуть полученный файл
     * @param string $filePath
     * @param int $cutOffDepth Количество уровней фигурных скобок, которые необходимо пройти, прежде чем игнорировать содержимое
     * @return string
     */
    public static function removeContent(string $filePath, int $cutOffDepth = 1): string
    {
        // удаляем комментарии и пробелы
        $contents = php_strip_whitespace($filePath);

        if ('' === trim($contents)) {
            return $contents;
        }

        if (!preg_match('/\b(?:class|interface)/i', $contents)) {
            return '';
        }

        // токенизируем содержимое файла
        $tokens = token_get_all($contents);
        $cleanContents = '';
        $depth = 0;
        $startCounting = false;
        // перебираем все токены и сохраняем только те токены, которые находятся за пределами $cutOffDepth
        foreach ($tokens as $token) {
            // сохраняем только строковый литерал токена
            if (!is_array($token)) {
                $token = [
                    T_STRING,
                    $token,
                    null
                ];
            }

            // учитывается только в том случае, если мы видим ключевое слово class/interface
            $targetTokens = [T_CLASS, T_INTERFACE];

            if (!$startCounting && in_array($token[0], $targetTokens)) {
                $startCounting = true;
            }

            // используем фигурные скобки как признак глубины
            if ($token[1] === '{') {
                if ($depth < $cutOffDepth) {
                    $cleanContents .= $token[1];
                }
                if ($startCounting) {
                    ++$depth;
                }
            } elseif ($token[1] === '}') {
                if ($startCounting) {
                    --$depth;

                    // прекращаем подсчет, если мы только что вышли из объявления class/interface
                    if ($depth <= 0) {
                        $startCounting = false;
                    }
                }
                if ($depth < $cutOffDepth) {
                    $cleanContents .= $token[1];
                }
            } elseif ($depth < $cutOffDepth) {
                $cleanContents .= $token[1];
            }
        }

        return trim($cleanContents);
    }
}