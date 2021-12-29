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
 * Object representation of the subpalettes part of a data container array.
 */
class Subpalettes extends Schema
{
    public function palette(string $key): Palette
    {
        return Palette::createFromString($key, $this->get($key));
    }
}
