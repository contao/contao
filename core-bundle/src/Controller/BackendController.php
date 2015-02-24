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
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles the Contao backend routes.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class BackendController
{
    public function mainAction()
    {
        return $this->getResponseForController(new BackendMain());
    }

    public function loginAction()
    {
        return $this->getResponseForController(new BackendIndex());
    }

    public function installAction()
    {
        return $this->getResponseForController(new BackendInstall());
    }

    public function passwordAction()
    {
        return $this->getResponseForController(new BackendPassword());
    }

    public function previewAction()
    {
        return $this->getResponseForController(new BackendPreview());
    }

    public function changelogAction()
    {
        return $this->getResponseForController(new BackendChangelog());
    }

    public function confirmAction()
    {
        return $this->getResponseForController(new BackendConfirm());
    }

    public function fileAction()
    {
        return $this->getResponseForController(new BackendFile());
    }

    public function helpAction()
    {
        return $this->getResponseForController(new BackendHelp());
    }

    public function pageAction()
    {
        return $this->getResponseForController(new BackendPage());
    }

    public function popupAction()
    {
        return $this->getResponseForController(new BackendPopup());
    }

    public function switchAction()
    {
        return $this->getResponseForController(new BackendSwitch());
    }

    private function getResponseForController($controller)
    {
        ob_start();

        $controller->run();

        return new Response(ob_get_clean());
    }
}
