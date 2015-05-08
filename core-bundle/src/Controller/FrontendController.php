<?php

/*
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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles the Contao frontend routes.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 * @author Leo Feyer <https://github.com/leofeyer>
 *
 * @Route(defaults={"_scope" = "frontend"})
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
     *
     * @Route("/_contao/cron", name="contao_frontend_cron")
     */
    public function cronAction()
    {
        $controller = new FrontendCron();

        return $controller->run();
    }

    /**
     * Renders the content syndication dialog.
     *
     * @return RedirectResponse
     *
     * @Route("/_contao/share", name="contao_frontend_share")
     */
    public function shareAction()
    {
        $controller = new FrontendShare();

        return $controller->run();
    }
}
