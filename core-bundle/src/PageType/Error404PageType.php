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

use Contao\PageError404;

class Error404PageType extends AbstractSinglePageType implements HasLegacyPageInterface
{
    public function getLegacyPageClass(): string
    {
        return PageError404::class;
    }
}
