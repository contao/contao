<?php

namespace Contao\Fixtures;

use Symfony\Component\HttpFoundation\Response;

class PageError401
{
    public function getResponse()
    {
        return new Response('', 401);
    }
}
