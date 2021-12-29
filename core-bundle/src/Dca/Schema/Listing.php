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
 * Object representation of the list part of a data container array.
 */
class Listing extends Schema
{
    protected array $schemaClasses = [
        'operations' => OperationsCollection::class,
        'sorting' => Sorting::class,
    ];

    public function operations(): OperationsCollection
    {
        return $this->getSchema('operations', OperationsCollection::class);
    }

    public function sorting(): Sorting
    {
        return $this->getSchema('sorting', Sorting::class);
    }
}
