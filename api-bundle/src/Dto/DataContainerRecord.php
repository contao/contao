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

final class DataContainerRecord
{
    /**
     * The identifier is nullable because the same transport object is used for
     * collection payloads and create operations before a record has been persisted.
     *
     * @param array<string, mixed> $data
     */
    public function __construct(
        public readonly string $table,
        public array $data = [],
        public readonly int|string|null $id = null,
    ) {
    }
}
