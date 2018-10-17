<?php

namespace Contao\CoreBundle\Tests\Fixtures\Exception;

use Contao\CoreBundle\Exception\ResponseException;
use Symfony\Component\HttpFoundation\Response;

class PageErrorResponseException
{
    public function getResponse()
    {
        throw new ResponseException(new Response('foo'));
    }
}
