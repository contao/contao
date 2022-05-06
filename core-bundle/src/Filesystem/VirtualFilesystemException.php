<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Filesystem;

/**
 * @experimental
 */
class VirtualFilesystemException extends \RuntimeException
{
    public const UNABLE_TO_CHECK_IF_FILE_EXISTS = 0;
    public const UNABLE_TO_CHECK_IF_DIRECTORY_EXISTS = 1;
    public const UNABLE_TO_READ = 2;
    public const UNABLE_TO_WRITE = 3;
    public const UNABLE_TO_DELETE = 4;
    public const UNABLE_TO_DELETE_DIRECTORY = 5;
    public const UNABLE_TO_CREATE_DIRECTORY = 6;
    public const UNABLE_TO_COPY = 7;
    public const UNABLE_TO_MOVE = 8;
    public const UNABLE_TO_LIST_CONTENTS = 9;
    public const UNABLE_TO_RETRIEVE_METADATA = 10;

    private string $path;

    private function __construct(string $path, string $message, int $code, \Throwable $previous)
    {
        $this->path = $path;

        parent::__construct($message, $code, $previous);
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public static function unableToCheckIfFileExists(string $path, \Throwable $previous = null): self
    {
        return new self(
            $path,
            sprintf('Unable to check if a file exists at "%s".', $path),
            self::UNABLE_TO_CHECK_IF_FILE_EXISTS,
            $previous
        );
    }

    public static function unableToCheckIfDirectoryExists(string $path, \Throwable $previous = null): self
    {
        return new self(
            $path,
            sprintf('Unable to check if a directory exists at "%s".', $path),
            self::UNABLE_TO_CHECK_IF_DIRECTORY_EXISTS,
            $previous
        );
    }

    public static function unableToRead(string $path, \Throwable $previous = null): self
    {
        return new self(
            $path,
            sprintf('Unable to read from "%s".', $path),
            self::UNABLE_TO_READ,
            $previous
        );
    }

    public static function unableToWrite(string $path, \Throwable $previous = null): self
    {
        return new self(
            $path,
            sprintf('Unable to write to "%s".', $path),
            self::UNABLE_TO_WRITE,
            $previous
        );
    }

    public static function unableToDelete(string $path, \Throwable $previous = null): self
    {
        return new self(
            $path,
            sprintf('Unable to delete file at "%s".', $path),
            self::UNABLE_TO_DELETE,
            $previous
        );
    }

    public static function unableToDeleteDirectory(string $path, \Throwable $previous = null): self
    {
        return new self(
            $path,
            sprintf('Unable to delete directory at "%s".', $path),
            self::UNABLE_TO_DELETE_DIRECTORY,
            $previous
        );
    }

    public static function unableToCreateDirectory(string $path, \Throwable $previous = null): self
    {
        return new self(
            $path,
            sprintf('Unable to create directory at "%s".', $path),
            self::UNABLE_TO_CREATE_DIRECTORY,
            $previous
        );
    }

    public static function unableToCopy(string $pathFrom, string $pathTo, \Throwable $previous = null): self
    {
        return new self(
            $pathFrom,
            sprintf('Unable to copy file from "%s" to "%s".', $pathFrom, $pathTo),
            self::UNABLE_TO_COPY,
            $previous
        );
    }

    public static function unableToMove(string $pathFrom, string $pathTo, \Throwable $previous = null): self
    {
        return new self(
            $pathFrom,
            sprintf('Unable to move file from "%s" to "%s".', $pathFrom, $pathTo),
            self::UNABLE_TO_MOVE,
            $previous
        );
    }

    public static function unableToListContents(string $path, \Throwable $previous = null): self
    {
        return new self(
            $path,
            sprintf('Unable to list contents from "%s".', $path),
            self::UNABLE_TO_LIST_CONTENTS,
            $previous
        );
    }

    public static function unableToRetrieveMetadata(string $path, \Throwable $previous = null): self
    {
        return new self(
            $path,
            sprintf('Unable to retrieve metadata from "%s".', $path),
            self::UNABLE_TO_RETRIEVE_METADATA,
            $previous
        );
    }
}
