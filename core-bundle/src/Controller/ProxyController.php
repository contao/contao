<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Controller;

use Contao\BackendMain;
use Symfony\Component\HttpFoundation\Response;

/**
 * Runs a Contao controller.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ProxyController
{
    /**
     * @var BackendMain|object
     */
    private $controller;

    /**
     * Constructor.
     *
     * @param BackendMain|object $controller
     */
    public function __construct($controller)
    {
        $this->controller = $controller;
    }

    /**
     * Runs the controller and returns a response object.
     *
     * @return Response The response object
     */
    public function run()
    {
        ob_start();

        $this->controller->run();

        return new Response(ob_get_clean());
    }
}
