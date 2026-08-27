<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Dto;

use ApiPlatform\Metadata\ApiProperty;
use Contao\ApiBundle\Validator\Constraints\DataContainerRecordSchema;

#[DataContainerRecordSchema]
final class DataContainerRecord
{
    public function __construct(
        public readonly string $table,
        public array $data = [],
        #[ApiProperty(identifier: true)]
        public readonly int|string|null $id = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(string $table, array $data, int|string|null $id = null): self
    {
        return new self($table, $data, $id);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $normalized = $this->data;

        if (null !== $this->id) {
            $normalized = ['id' => $this->id] + $normalized;
        }

        return $normalized;
    }
}
