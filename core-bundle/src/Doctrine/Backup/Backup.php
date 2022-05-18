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

class Backup implements \Stringable
{
    final public const DATETIME_FORMAT = 'YmdHis';
    final public const VALID_BACKUP_NAME_REGEX = '@^[^/]*__(\d{4}\d{2}\d{2}\d{2}\d{2}\d{2})\.sql(\.gz)?$@';

    private string $filename;
    private \DateTimeInterface $createdAt;
    private int $size = 0;

    /**
     * @throws BackupManagerException
     */
    public function __construct(string $filename)
    {
        $this->filename = self::validateFileName($filename);
        $this->createdAt = self::extractDatetime($filename);
    }

    public function __toString(): string
    {
        return sprintf('[Backup]: %s', $this->getFilename());
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * Size of the backup in bytes.
     */
    public function getSize(): int
    {
        return $this->size;
    }

    public function setSize(int $size): self
    {
        $this->size = $size;

        return $this;
    }

    public static function createNew(\DateTime $dateTime = null): self
    {
        $now = $dateTime ?? new \DateTime('now');
        $now->setTimezone(new \DateTimeZone('UTC'));

        return new self(sprintf('backup__%s.sql.gz', $now->format(self::DATETIME_FORMAT)));
    }

    public function toArray(): array
    {
        return [
            'createdAt' => $this->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'size' => $this->getSize(),
            'name' => $this->getFilename(),
        ];
    }

    private static function extractDatetime(string $filepath): \DateTimeInterface
    {
        preg_match(self::VALID_BACKUP_NAME_REGEX, $filepath, $matches);

        // No need to check for false here because the regex does not allow a format that does not work.
        // PHP will even turn month 42 into a valid datetime.
        return \DateTime::createFromFormat(self::DATETIME_FORMAT, $matches[1], new \DateTimeZone('UTC'));
    }

    private static function validateFileName(string $filename): string
    {
        if (!preg_match(self::VALID_BACKUP_NAME_REGEX, $filename)) {
            throw new BackupManagerException(sprintf('The filename "%s" does not match "%s"', $filename, self::VALID_BACKUP_NAME_REGEX));
        }

        return $filename;
    }
}
