<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Search\Backend;

class Facet
{
    public function __construct(
        public string $key,
        public string $label,
        public int $count,
    ) {
    }
}
