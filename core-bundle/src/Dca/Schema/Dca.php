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

class Dca extends Schema
{
    protected array $schemaClasses = [
        'config' => Config::class,
        'list' => Listing::class,
        'palettes' => Palettes::class,
        'subpalettes' => Subpalettes::class,
    ];

    public function config(): Config
    {
        return $this->getSchema('config', Config::class);
    }

    public function list(): Listing
    {
        return $this->getSchema('list', Listing::class);
    }

    public function palettes(): Palettes
    {
        return $this->getSchema('palettes', Palettes::class);
    }

    public function subpalettes(): Subpalettes
    {
        return $this->getSchema('subpalettes', Subpalettes::class);
    }

    public function fields(): FieldCollection
    {
        return $this->getSchema('fields', FieldCollection::class);
    }
}
