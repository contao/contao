<?php

namespace Contao\Fixtures;

use Symfony\Component\HttpFoundation\Response;

class PageError403
{
    public function getResponse()
    {
        return new Response('', 403);
    }
}
