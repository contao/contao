<?php

namespace Contao\Fixtures;

use Symfony\Component\HttpFoundation\Response;

class Frontend
{
    public static function getResponseFromCache()
    {
        return new Response();
    }

    public static function indexPageIfApplicable()
    {
        // ignore
    }
}
