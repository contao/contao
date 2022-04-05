<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Security\DataContainer;

class DataContainerSubject
{
    public function __construct(
        public readonly string $table,
        public readonly int|string|null $id = null,
        public readonly array $attributes = []
    ) {
    }

    public function __toString(): string
    {
        $subject = [];
        $subject[] = 'Table: '.$this->table;

        if ($id = $this->id) {
            $subject[] = 'ID: '.$id;
        }

        if ($attributes = $this->attributes) {
            $subject[] = 'Attributes: '.json_encode($attributes);
        }

        return sprintf('[Subject: %s]', implode('; ', $subject));
    }
}
