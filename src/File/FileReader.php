<?php

declare(strict_types=1);

namespace SensioLabs\Deptrac\File;

final class FileReader
{
    public static function read(string $fileName): string
    {
        if (!is_file($fileName)) {
            throw CouldNotReadFileException::fromFilename($fileName);
        }
        $contents = @file_get_contents($fileName);
        if (false === $contents) {
            throw CouldNotReadFileException::fromFilename($fileName);
        }

        return $contents;
    }
}
