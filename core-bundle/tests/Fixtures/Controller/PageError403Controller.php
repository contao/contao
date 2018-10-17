<?php

namespace Contao\CoreBundle\Tests\Fixtures\Controller;

use Symfony\Component\HttpFoundation\Response;

class PageError403Controller
{
    public function getResponse()
    {
        return new Response('', 403);
    }
}
