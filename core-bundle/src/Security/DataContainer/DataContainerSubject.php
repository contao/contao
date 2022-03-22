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
    private string $table;
    private int|string|null $id;
    private array $attributes;

    public function __construct(string $table, int|string|null $id = null, array $attributes = [])
    {
        $this->table = $table;
        $this->id = $id;
        $this->attributes = $attributes;
    }

    public function __toString(): string
    {
        $subject = [];
        $subject[] = 'Table: '.$this->getTable();

        if ($id = $this->getId()) {
            $subject[] = 'ID: '.$id;
        }

        if ($attributes = $this->getAttributes()) {
            $subject[] = 'Attributes: '.json_encode($attributes);
        }

        return sprintf('[Subject: %s]', implode('; ', $subject));
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getId(): int|string|null
    {
        return $this->id;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }
}
