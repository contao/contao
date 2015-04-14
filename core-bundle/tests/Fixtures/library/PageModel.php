<?php

namespace Contao\Fixtures;

use Contao\Result;

class PageModel
{
    public static function findPublishedRootPages()
    {
        $page1           = new \stdClass();
        $page1->dns      = '';
        $page1->fallback = '1';
        $page1->language = 'en';

        $page2           = new \stdClass();
        $page2->dns      = 'test.com';
        $page2->fallback = '';
        $page2->language = 'en';

        return new Result([$page1, $page2]);
    }
}
