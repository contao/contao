<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Fixtures\Contao;

use Contao\Widget;

class FoobarWidget extends Widget
{
    public function generate()
    {
        return '';
    }
}
