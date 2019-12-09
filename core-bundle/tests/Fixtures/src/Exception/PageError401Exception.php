<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Fixtures\Exception;

use Contao\CoreBundle\Exception\InsufficientAuthenticationException;

class PageError401Exception
{
    public function getResponse(): void
    {
        throw new InsufficientAuthenticationException('Not authenticated');
    }
}
