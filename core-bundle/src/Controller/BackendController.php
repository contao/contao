<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Controller;

use Contao\BackendConfirm;
use Contao\BackendFile;
use Contao\BackendHelp;
use Contao\BackendIndex;
use Contao\BackendInstall;
use Contao\BackendMain;
use Contao\BackendPage;
use Contao\BackendPassword;
use Contao\BackendPopup;
use Contao\BackendPreview;
use Contao\BackendSwitch;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles the Contao backend routes.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 * @author Leo Feyer <https://github.com/leofeyer>
 *
 * @Route("/contao", defaults={"_scope" = "backend"})
 */
class BackendController extends Controller
{
    /**
     * Runs the main back end controller.
     *
     * @return Response
     *
     * @Route("", name="contao_backend")
     */
    public function mainAction()
    {
        $this->container->get('contao.framework')->initialize();

        $controller = new BackendMain();

        return $controller->run();
    }

    /**
     * Renders the back end login form.
     *
     * @return Response
     *
     * @Route("/login", name="contao_backend_login")
     */
    public function loginAction()
    {
        $this->container->get('contao.framework')->initialize();

        $controller = new BackendIndex();

        return $controller->run();
    }

    /**
     * Renders the "set new password" form.
     *
     * @return Response
     *
     * @Route("/password", name="contao_backend_password")
     */
    public function passwordAction()
    {
        $this->container->get('contao.framework')->initialize();

        $controller = new BackendPassword();

        return $controller->run();
    }

    /**
     * Renders the front end preview.
     *
     * @return Response
     *
     * @Route("/preview", name="contao_backend_preview")
     */
    public function previewAction()
    {
        $this->container->get('contao.framework')->initialize();

        $controller = new BackendPreview();

        return $controller->run();
    }

    /**
     * Renders the "invalid request token" screen.
     *
     * @return Response
     *
     * @Route("/confirm", name="contao_backend_confirm")
     */
    public function confirmAction()
    {
        $this->container->get('contao.framework')->initialize();

        $controller = new BackendConfirm();

        return $controller->run();
    }

    /**
     * Renders the file picker.
     *
     * @return Response
     *
     * @Route("/file", name="contao_backend_file")
     */
    public function fileAction()
    {
        $this->container->get('contao.framework')->initialize();

        $controller = new BackendFile();

        return $controller->run();
    }

    /**
     * Renders the help content.
     *
     * @return Response
     *
     * @Route("/help", name="contao_backend_help")
     */
    public function helpAction()
    {
        $this->container->get('contao.framework')->initialize();

        $controller = new BackendHelp();

        return $controller->run();
    }

    /**
     * Renders the page picker.
     *
     * @return Response
     *
     * @Route("/page", name="contao_backend_page")
     */
    public function pageAction()
    {
        $this->container->get('contao.framework')->initialize();

        $controller = new BackendPage();

        return $controller->run();
    }

    /**
     * Renders the pop-up content.
     *
     * @return Response
     *
     * @Route("/popup", name="contao_backend_popup")
     */
    public function popupAction()
    {
        $this->container->get('contao.framework')->initialize();

        $controller = new BackendPopup();

        return $controller->run();
    }

    /**
     * Renders the front end preview switcher.
     *
     * @return Response
     *
     * @Route("/switch", name="contao_backend_switch")
     */
    public function switchAction()
    {
        $this->container->get('contao.framework')->initialize();

        $controller = new BackendSwitch();

        return $controller->run();
    }
}
