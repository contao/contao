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
        $controller = new BackendIndex();

        return $controller->run();
    }

    /**
     * Renders the install tool.
     *
     * @return Response
     *
     * @todo Make the install tool stand-alone
     *
     * @Route("/install", name="contao_backend_install")
     */
    public function installAction()
    {
        ob_start();

        $controller = new BackendInstall();
        $controller->run();

        return new Response(ob_get_clean());
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
        $controller = new BackendSwitch();

        return $controller->run();
    }
}
