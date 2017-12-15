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

use Contao\BackendAlerts;
use Contao\BackendConfirm;
use Contao\BackendFile;
use Contao\BackendHelp;
use Contao\BackendIndex;
use Contao\BackendMain;
use Contao\BackendPage;
use Contao\BackendPassword;
use Contao\BackendPopup;
use Contao\BackendPreview;
use Contao\BackendSwitch;
use Contao\CoreBundle\Picker\PickerConfig;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @Route(defaults={"_scope" = "backend", "_token_check" = true})
 */
class BackendController extends Controller
{
    /**
     * @return Response
     *
     * @Route("/contao", name="contao_backend")
     */
    public function mainAction(): Response
    {
        $this->container->get('contao.framework')->initialize();

        $controller = new BackendMain();

        return $controller->run();
    }

    /**
     * @return Response
     *
     * @Route("/contao/login", name="contao_backend_login")
     */
    public function loginAction(): Response
    {
        $this->container->get('contao.framework')->initialize();

        $controller = new BackendIndex();

        return $controller->run();
    }

    /**
     * @return Response
     *
     * @Route("/contao/password", name="contao_backend_password")
     */
    public function passwordAction(): Response
    {
        $this->container->get('contao.framework')->initialize();

        $controller = new BackendPassword();

        return $controller->run();
    }

    /**
     * @return Response
     *
     * @Route("/contao/preview", name="contao_backend_preview")
     */
    public function previewAction(): Response
    {
        $this->container->get('contao.framework')->initialize();

        $controller = new BackendPreview();

        return $controller->run();
    }

    /**
     * @return Response
     *
     * @Route("/contao/confirm", name="contao_backend_confirm")
     */
    public function confirmAction(): Response
    {
        $this->container->get('contao.framework')->initialize();

        $controller = new BackendConfirm();

        return $controller->run();
    }

    /**
     * @return Response
     *
     * @Route("/contao/file", name="contao_backend_file")
     */
    public function fileAction(): Response
    {
        $this->container->get('contao.framework')->initialize();

        $controller = new BackendFile();

        return $controller->run();
    }

    /**
     * @return Response
     *
     * @Route("/contao/help", name="contao_backend_help")
     */
    public function helpAction(): Response
    {
        $this->container->get('contao.framework')->initialize();

        $controller = new BackendHelp();

        return $controller->run();
    }

    /**
     * @return Response
     *
     * @Route("/contao/page", name="contao_backend_page")
     */
    public function pageAction(): Response
    {
        $this->container->get('contao.framework')->initialize();

        $controller = new BackendPage();

        return $controller->run();
    }

    /**
     * @return Response
     *
     * @Route("/contao/popup", name="contao_backend_popup")
     */
    public function popupAction(): Response
    {
        $this->container->get('contao.framework')->initialize();

        $controller = new BackendPopup();

        return $controller->run();
    }

    /**
     * @return Response
     *
     * @Route("/contao/switch", name="contao_backend_switch")
     */
    public function switchAction(): Response
    {
        $this->container->get('contao.framework')->initialize();

        $controller = new BackendSwitch();

        return $controller->run();
    }

    /**
     * @return Response
     *
     * @Route("/contao/alerts", name="contao_backend_alerts")
     */
    public function alertsAction(): Response
    {
        $this->container->get('contao.framework')->initialize();

        $controller = new BackendAlerts();

        return $controller->run();
    }

    /**
     * Redirects the user to the Contao back end and adds the picker query parameter.
     * It will determine the current provider URL based on the value, which is usually
     * read dynamically via JavaScript.
     *
     * @param Request $request
     *
     * @throws BadRequestHttpException
     *
     * @return RedirectResponse
     *
     * @Route("/_contao/picker", name="contao_backend_picker")
     */
    public function pickerAction(Request $request): RedirectResponse
    {
        $extras = [];

        if ($request->query->has('extras')) {
            $extras = $request->query->get('extras');

            if (!\is_array($extras)) {
                throw new BadRequestHttpException('Invalid picker extras');
            }
        }

        $config = new PickerConfig($request->query->get('context'), $extras, $request->query->get('value'));
        $picker = $this->container->get('contao.picker.builder')->create($config);

        if (null === $picker) {
            throw new BadRequestHttpException('Unsupported picker context');
        }

        return new RedirectResponse($picker->getCurrentUrl());
    }

    /**
     * Symfony will un-authenticate the user automatically by calling this route.
     *
     * @Route("/contao/logout", name="contao_backend_logout")
     */
    public function logoutAction(): void
    {
    }
}
