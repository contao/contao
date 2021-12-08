<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Event;

class DbafsMetadataEvent
{
    private string $table;

    /**
     * @var array<string, mixed>
     */
    private array $row;

    private array $extraMetadata = [];

    /**
     * @param array<string, mixed> $row
     */
    public function __construct(string $table, array $row)
    {
        $this->table = $table;
        $this->row = $row;

        foreach (['path', 'uuid'] as $mandatoryKey) {
            if (null === ($value = $row[$mandatoryKey] ?? null)) {
                throw new \InvalidArgumentException("Row must contain key '$mandatoryKey'.");
            }

            if ('string' !== ($type = \gettype($value))) {
                throw new \InvalidArgumentException("Row key '$mandatoryKey' must be of type string, got $type.");
            }
        }
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getPath(): string
    {
        return $this->row['path'];
    }

    public function getUuid(): string
    {
        return $this->row['uuid'];
    }

    /**
     * @return array<string, mixed>
     */
    public function getRow(): array
    {
        return $this->row;
    }

    public function getExtraMetadata(): array
    {
        return $this->extraMetadata;
    }

    /**
     * @param mixed $value
     */
    public function set(string $key, $value): void
    {
        $this->extraMetadata[$key] = $value;
    }
}
