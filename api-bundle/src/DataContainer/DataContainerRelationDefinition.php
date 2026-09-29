<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\DataContainer;

final readonly class DataContainerRelationDefinition
{
    public function __construct(
        public string $table,
        public string $field = 'id',
    ) {
        if ('' === $this->table || '' === $this->field) {
            throw new \InvalidArgumentException('The relation table and field must not be empty.');
        }
    }
}
