<?php

namespace Contao\Fixtures;

class PageError404
{
    public static $getResponse;

    public function getResponse($pageId, $strDomain=null, $strHost=null, $blnUnusedGet=false)
    {
        return call_user_func(static::$getResponse, $pageId, $strDomain, $strHost, $blnUnusedGet);
    }
}
