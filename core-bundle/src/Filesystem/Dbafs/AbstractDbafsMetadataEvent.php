<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Filesystem\Dbafs;

use Symfony\Component\Uid\Uuid;

/**
 * @internal
 */
class AbstractDbafsMetadataEvent
{
    /**
     * @var array<string, mixed>
     */
    protected array $row;

    /**
     * @var array<string, mixed>
     */
    protected array $extraMetadata;

    private string $table;

    /**
     * @param array<string, mixed> $row
     * @param array<string, mixed> $extraMetadata
     */
    public function __construct(string $table, array $row, array $extraMetadata = [])
    {
        $this->table = $table;
        $this->row = $row;
        $this->extraMetadata = $extraMetadata;

        foreach (['path', 'uuid'] as $mandatoryKey) {
            if (null === ($value = $row[$mandatoryKey] ?? null)) {
                throw new \InvalidArgumentException(sprintf('Row must contain key "%s".', $mandatoryKey));
            }

            if ('string' !== ($type = \gettype($value))) {
                throw new \InvalidArgumentException(sprintf('Row key "%s" must be of type string, got %s.', $mandatoryKey, $type));
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

    public function getUuid(): Uuid
    {
        return Uuid::fromBinary($this->row['uuid']);
    }

    /**
     * @return array<string, mixed>
     */
    public function getRow(): array
    {
        return $this->row;
    }

    /**
     * @return array<string, mixed>
     */
    public function getExtraMetadata(): array
    {
        return $this->extraMetadata;
    }
}
