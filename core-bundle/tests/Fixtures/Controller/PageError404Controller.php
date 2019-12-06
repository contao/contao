<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Fixtures\Controller;

use Symfony\Component\HttpFoundation\Response;

class PageError404Controller
{
    public function getResponse()
    {
        return new Response('', 404);
    }
}
