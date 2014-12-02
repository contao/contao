<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @license LGPL-3.0+
 */

use Symfony\Component\HttpFoundation\Response;

class Frontend
{
    public static function indexPageIfApplicable(Response $objResponse)
    {
        return true;
    }

    public static function getResponseFromCache()
    {
        return new Response();
    }
}
