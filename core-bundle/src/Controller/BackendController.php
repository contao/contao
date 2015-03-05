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
     */
    public function switchAction()
    {
        $controller = new BackendSwitch();

        return $controller->run();
    }
}
