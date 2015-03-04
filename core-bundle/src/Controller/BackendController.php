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
        $proxy = new ProxyController(new BackendMain());

        return $proxy->run();
    }

    /**
     * Renders the back end login form.
     *
     * @return Response
     */
    public function loginAction()
    {
        $proxy = new ProxyController(new BackendIndex());

        return $proxy->run();
    }

    /**
     * Renders the install tool.
     *
     * @return Response
     */
    public function installAction()
    {
        $proxy = new ProxyController(new BackendInstall());

        return $proxy->run();
    }

    /**
     * Renders the "set new password" form.
     *
     * @return Response
     */
    public function passwordAction()
    {
        $proxy = new ProxyController(new BackendPassword());

        return $proxy->run();
    }

    /**
     * Renders the front end preview.
     *
     * @return Response
     */
    public function previewAction()
    {
        $proxy = new ProxyController(new BackendPreview());

        return $proxy->run();
    }

    /**
     * Renders the change log viewer.
     *
     * @return Response
     */
    public function changelogAction()
    {
        $proxy = new ProxyController(new BackendChangelog());

        return $proxy->run();
    }

    /**
     * Renders the "invalid request token" screen.
     *
     * @return Response
     */
    public function confirmAction()
    {
        $proxy = new ProxyController(new BackendConfirm());

        return $proxy->run();
    }

    /**
     * Renders the file picker.
     *
     * @return Response
     */
    public function fileAction()
    {
        $proxy = new ProxyController(new BackendFile());

        return $proxy->run();
    }

    /**
     * Renders the help content.
     *
     * @return Response
     */
    public function helpAction()
    {
        $proxy = new ProxyController(new BackendHelp());

        return $proxy->run();
    }

    /**
     * Renders the page picker.
     *
     * @return Response
     */
    public function pageAction()
    {
        $proxy = new ProxyController(new BackendPage());

        return $proxy->run();
    }

    /**
     * Renders the pop-up content.
     *
     * @return Response
     */
    public function popupAction()
    {
        $proxy = new ProxyController(new BackendPopup());

        return $proxy->run();
    }

    /**
     * Renders the front end preview switcher.
     *
     * @return Response
     */
    public function switchAction()
    {
        $proxy = new ProxyController(new BackendSwitch());

        return $proxy->run();
    }
}
