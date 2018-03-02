<?php

namespace Contao\Fixtures;

use Contao\CoreBundle\Exception\InsufficientAuthenticationException;

class PageError401Exception
{
    public function getResponse()
    {
        throw new InsufficientAuthenticationException('Not authenticated');
    }
}
