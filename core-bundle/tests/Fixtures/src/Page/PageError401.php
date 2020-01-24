<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Fixtures\Page;

use Symfony\Component\HttpFoundation\Response;

class PageError401
{
    public static $exception;

    public function getResponse(): Response
    {
        if (self::$exception) {
            throw self::$exception;
        }

        return new Response('foo');
    }
}
