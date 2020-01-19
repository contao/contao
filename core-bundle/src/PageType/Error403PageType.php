<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\PageType;

use Contao\PageError403;

class Error403PageType extends AbstractPageType implements HasLegacyPageInterface
{
    public function getLegacyPageClass(): string
    {
        return PageError403::class;
    }
}
