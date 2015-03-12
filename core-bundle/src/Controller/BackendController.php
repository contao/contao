<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Controller;

use Contao\BackendChangelog;
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
 */
class BackendController extends Controller
{
    /**
     * Runs the main back end controller.
     *
     * @return Response
     *
     * @Route("/contao", name="contao_backend", defaults={"_scope" = "backend"})
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
     * @Route("/contao/login", name="contao_backend_login", defaults={"_scope" = "backend"})
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
     * @Route("/contao/install", name="contao_backend_install", defaults={"_scope" = "backend"})
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
     * @Route("/contao/password", name="contao_backend_password", defaults={"_scope" = "backend"})
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
     * @Route("/contao/preview", name="contao_backend_preview", defaults={"_scope" = "backend"})
     */
    public function previewAction()
    {
        $controller = new BackendPreview();

        return $controller->run();
    }

    /**
     * Renders the change log viewer.
     *
     * @return Response
     *
     * @Route("/contao/changelog", name="contao_backend_changelog", defaults={"_scope" = "backend"})
     */
    public function changelogAction()
    {
        $controller = new BackendChangelog();

        return $controller->run();
    }

    /**
     * Renders the "invalid request token" screen.
     *
     * @return Response
     *
     * @Route("/contao/confirm", name="contao_backend_confirm", defaults={"_scope" = "backend"})
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
     * @Route("/contao/file", name="contao_backend_file", defaults={"_scope" = "backend"})
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
     * @Route("/contao/help", name="contao_backend_help", defaults={"_scope" = "backend"})
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
     * @Route("/contao/page", name="contao_backend_page", defaults={"_scope" = "backend"})
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
     * @Route("/contao/popup", name="contao_backend_popup", defaults={"_scope" = "backend"})
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
     * @Route("/contao/switch", name="contao_backend_switch", defaults={"_scope" = "backend"})
     */
    public function switchAction()
    {
        $controller = new BackendSwitch();

        return $controller->run();
    }
}
