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
use Webmozart\PathUtil\Path;

class Backup
{
    public const DATETIME_FORMAT = 'YmdHis';
    public const VALID_BACKUP_NAME_REGEX = '@^.*__(\d{4}\d{2}\d{2}\d{2}\d{2}\d{2})\.sql(\.gz)?$@';

    private string $filepath;
    private \DateTimeInterface $createdAt;

    public function __construct(string $filepath)
    {
        $this->filepath = self::validateFilePath($filepath);
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

    public static function createNewAtPath(string $targetPath, \DateTime $dateTime = null): self
    {
        $targetPath = rtrim($targetPath, '/');
        $now = $dateTime ?? new \DateTime('now');
        $now->setTimezone(new \DateTimeZone('UTC'));
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
        preg_match(self::VALID_BACKUP_NAME_REGEX, $filepath, $matches);

        // No need to check for false here because the regex does not allow a format that does not work
        // PHP will even turn month 42 into a valid datetime
        return \DateTime::createFromFormat(self::DATETIME_FORMAT, $matches[1], new \DateTimeZone('UTC'));
    }

    private static function validateFilePath(string $filepath): string
    {
        if (!preg_match(self::VALID_BACKUP_NAME_REGEX, $filepath)) {
            throw new BackupManagerException(sprintf('The filepath "%s" does not match "%s"', $filepath, self::VALID_BACKUP_NAME_REGEX));
        }

        return Path::normalize($filepath);
    }
}
