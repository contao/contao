<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Doctrine\Backup;

use Symfony\Component\Filesystem\Filesystem;

class Backup
{
    public const DATETIME_FORMAT = 'YmdHis';

    private string $filepath;
    private \DateTimeInterface $createdAt;

    public function __construct(string $filepath)
    {
        $this->filepath = $filepath;
        $this->createdAt = self::extractDatetime($filepath);
    }

    /**
     * Returns the size in bytes.
     */
    public function getSize(): int
    {
        return (int) filesize($this->getFilepath());
    }

    public function getHumanReadableSize(): string
    {
        $base = log($this->getSize()) / log(1024);
        $suffix = ['B', 'KB', 'MB', 'GB', 'TB'][floor($base)];

        return round(pow(1024, $base - floor($base)), 2).' '.$suffix;
    }

    public function getFilepath(): string
    {
        return $this->filepath;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public static function createNewAtPath(string $targetPath): self
    {
        $targetPath = rtrim($targetPath, '/');
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $filepath = sprintf('%s/backup__%s.sql.gz', $targetPath, $now->format(self::DATETIME_FORMAT));

        (new Filesystem())->dumpFile($filepath, '');

        return new self($filepath);
    }

    public function toArray(): array
    {
        return [
            'createdAt' => $this->getCreatedAt()->format(\DateTimeInterface::ISO8601),
            'size' => $this->getSize(),
            'humanReadableSize' => $this->getHumanReadableSize(),
            'path' => $this->getFilepath(),
        ];
    }

    /**
     * @throws BackupManagerException
     */
    private static function extractDatetime(string $filepath): \DateTimeInterface
    {
        $chunks = explode('.', $filepath, 2); // Drops all extensions
        $chunks = explode('__', $chunks[0], 2);

        if (2 !== \count($chunks)) {
            throw new BackupManagerException('Invalid backup filename!');
        }

        try {
            $datetime = \DateTime::createFromFormat(self::DATETIME_FORMAT, $chunks[1], new \DateTimeZone('UTC'));
        } catch (\Exception $e) {
            throw new BackupManagerException('Invalid datetime format on backup filename!');
        }

        return $datetime;
    }
}
