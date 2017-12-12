<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
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
 * @Route(defaults={"_scope" = "frontend", "_token_check" = true})
 */
class FrontendController extends Controller
{
    /**
     * @return Response
     */
    public function indexAction(): Response
    {
        $this->container->get('contao.framework')->initialize();

        $controller = new FrontendIndex();

        return $controller->run();
    }

    /**
     * @return Response
     *
     * @Route("/_contao/cron", name="contao_frontend_cron")
     */
    public function cronAction(): Response
    {
        $this->container->get('contao.framework')->initialize();

        $controller = new FrontendCron();

        return $controller->run();
    }

    /**
     * @return RedirectResponse
     *
     * @Route("/_contao/share", name="contao_frontend_share")
     */
    public function shareAction(): RedirectResponse
    {
        $this->container->get('contao.framework')->initialize();

        $controller = new FrontendShare();

        return $controller->run();
    }

    /**
     * Symfony will authenticate the user automatically by calling this route.
     *
     * @Route("/_contao/login", name="contao_frontend_login")
     */
    public function loginAction(): void
    {
    }

    /**
     * Symfony will un-authenticate the user automatically by calling this route.
     *
     * @Route("/_contao/logout", name="contao_frontend_logout")
     */
    public function logoutAction(): void
    {
    }
}
