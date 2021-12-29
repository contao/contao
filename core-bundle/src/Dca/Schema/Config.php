<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Dca\Schema;

/**
 * Object representation of the config part of a data container array.
 */
class Config extends Schema
{
    protected array $schemaClasses = [
        '*_callback' => CallbackCollection::class,
    ];

    public function isClosed(): bool
    {
        return $this->is('closed');
    }

    public function isEditable(): bool
    {
        return !$this->is('notEditable');
    }

    public function usesVersioning(): bool
    {
        return $this->is('enableVersioning');
    }

    public function callback(string $name): CallbackCollection
    {
        return $this->getSchema($name.'_callback', CallbackCollection::class);
    }
}
