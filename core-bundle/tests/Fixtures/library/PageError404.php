<?php

namespace Contao\Fixtures;

use Symfony\Component\HttpFoundation\Response;

class PageError404
{
    public function getResponse()
    {
        return new Response('', 404);
    }
}
