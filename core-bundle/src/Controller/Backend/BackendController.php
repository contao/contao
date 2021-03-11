<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Controller\Backend;

use Contao\BackendAlerts;
use Contao\BackendConfirm;
use Contao\BackendFile;
use Contao\BackendHelp;
use Contao\BackendPage;
use Contao\BackendPassword;
use Contao\BackendPopup;
use Contao\CoreBundle\Controller\AbstractController;
use Contao\CoreBundle\Picker\PickerBuilderInterface;
use Contao\CoreBundle\Picker\PickerConfig;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @internal
 */
class BackendController extends AbstractController
{
    /**
     * Symfony will un-authenticate the user automatically by calling this route.
     *
     * @Route("/logout", name="contao_backend_logout")
     */
    public function logoutAction(): RedirectResponse
    {
        return $this->redirectToRoute('contao_backend_login');
    }

    /**
     * @Route("/password", name="contao_backend_password")
     */
    public function passwordAction(): Response
    {
        $this->initializeContaoFramework();

        $controller = new BackendPassword();

        return $controller->run();
    }

    /**
     * @Route("/confirm", name="contao_backend_confirm")
     */
    public function confirmAction(): Response
    {
        $this->initializeContaoFramework();

        $controller = new BackendConfirm();

        return $controller->run();
    }

    /**
     * @Route("/file", name="contao_backend_file")
     */
    public function fileAction(): Response
    {
        $this->initializeContaoFramework();

        $controller = new BackendFile();

        return $controller->run();
    }

    /**
     * @Route("/help", name="contao_backend_help")
     */
    public function helpAction(): Response
    {
        $this->initializeContaoFramework();

        $controller = new BackendHelp();

        return $controller->run();
    }

    /**
     * @Route("/page", name="contao_backend_page")
     */
    public function pageAction(): Response
    {
        $this->initializeContaoFramework();

        $controller = new BackendPage();

        return $controller->run();
    }

    /**
     * @Route("/popup", name="contao_backend_popup")
     */
    public function popupAction(): Response
    {
        $this->initializeContaoFramework();

        $controller = new BackendPopup();

        return $controller->run();
    }

    /**
     * @Route("/alerts", name="contao_backend_alerts")
     */
    public function alertsAction(): Response
    {
        $this->initializeContaoFramework();

        $controller = new BackendAlerts();

        return $controller->run();
    }

    /**
     * Redirects the user to the Contao back end and adds the picker query parameter.
     * It will determine the current provider URL based on the value, which is usually
     * read dynamically via JavaScript.
     *
     * @throws BadRequestHttpException
     *
     * @Route("/picker", name="contao_backend_picker")
     */
    public function pickerAction(Request $request): RedirectResponse
    {
        $extras = [];

        if ($request->query->has('extras')) {
            if ($request->query instanceof InputBag) {
                $extras = $request->query->all('extras');
            } else { /** @phpstan-ignore-line */
                // Backwards compatibility with symfony/http-foundation <5.0
                $extras = $request->query->get('extras');
            }

            if (empty($extras) || !\is_array($extras)) {
                throw new BadRequestHttpException('Invalid picker extras');
            }
        }

        $config = new PickerConfig($request->query->get('context'), $extras, $request->query->get('value'));
        $picker = $this->get('contao.picker.builder')->create($config);

        if (null === $picker) {
            throw new BadRequestHttpException('Unsupported picker context');
        }

        return new RedirectResponse($picker->getCurrentUrl());
    }

    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();

        $services['contao.picker.builder'] = PickerBuilderInterface::class;
        $services['uri_signer'] = 'uri_signer'; // TODO: adjust this once we are on Symfony 5 only (see https://github.com/symfony/symfony/pull/35298)

        return $services;
    }
}
