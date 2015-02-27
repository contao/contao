<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles the Contao frontend routes.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class FrontendController extends Controller
{
    // FIXME: add the phpDoc comments
    public function indexAction()
    {
        return $this->getResponseForController(new \FrontendIndex());
    }

    public function cronAction()
    {
        return $this->getResponseForController(new \FrontendCron());
    }

    public function shareAction()
    {
        return $this->getResponseForController(new \FrontendShare());
    }

    private function getResponseForController($controller)
    {
        ob_start();

        $controller->run();

        return new Response(ob_get_clean());
    }
}
