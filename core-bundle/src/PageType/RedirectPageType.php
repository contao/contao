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

use Contao\PageRedirect;

class RedirectPageType extends AbstractPageType implements HasLegacyPageInterface
{
    public function getLegacyPageClass(): string
    {
        return PageRedirect::class;
    }
}
