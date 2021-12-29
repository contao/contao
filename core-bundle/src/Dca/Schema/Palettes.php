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

use Contao\CoreBundle\Dca\Util\Palette;

/**
 * Object representation of the palettes part of a data container array.
 */
class Palettes extends Schema
{
    public function palette(string $key): Palette
    {
        return Palette::createFromString($key, $this->get($key));
    }
}
