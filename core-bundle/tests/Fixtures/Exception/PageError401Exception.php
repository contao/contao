<?php

namespace Contao\CoreBundle\Tests\Fixtures\Exception;

use Contao\CoreBundle\Exception\InsufficientAuthenticationException;

class PageError401Exception
{
    public function getResponse()
    {
        throw new InsufficientAuthenticationException('Not authenticated');
    }
}
