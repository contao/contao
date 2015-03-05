<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Controller;

use Contao\FrontendCron;
use Contao\FrontendIndex;
use Contao\FrontendShare;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles the Contao frontend routes.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class FrontendController extends Controller
{
    /**
     * Runs the main front end controller.
     *
     * @return Response
     */
    public function indexAction()
    {
        $controller = new FrontendIndex();

        return $controller->run();
    }

    /**
     * Runs the command scheduler.
     *
     * @return Response
     */
    public function cronAction()
    {
        $controller = new FrontendCron();

        return $controller->run();
    }

    /**
     * Renders the content syndication dialog.
     *
     * @return Response
     */
    public function shareAction()
    {
        $controller = new FrontendShare();

        return $controller->run();
    }
}
