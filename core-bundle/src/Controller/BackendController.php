<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
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
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Picker\PickerBuilderInterface;
use Contao\CoreBundle\Picker\PickerConfig;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(defaults={"_scope" = "backend", "_token_check" = true})
 */
class BackendController extends AbstractController
{
    /**
     * @Route("/contao", name="contao_backend")
     */
    public function mainAction(): Response
    {
        $this->get('contao.framework')->initialize();

        $controller = new BackendMain();

        return $controller->run();
    }

    /**
     * @Route("/contao/login", name="contao_backend_login")
     */
    public function loginAction(Request $request): Response
    {
        $this->get('contao.framework')->initialize();

        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) {
            $queryString = '';

            if ($request->query->has('referer')) {
                $queryString = '?'.base64_decode($request->query->get('referer'), true);
            }

            return new RedirectResponse($this->generateUrl('contao_backend').$queryString);
        }

        $controller = new BackendIndex();

        return $controller->run();
    }

    /**
     * Symfony will un-authenticate the user automatically by calling this route.
     *
     * @Route("/contao/logout", name="contao_backend_logout")
     */
    public function logoutAction(): RedirectResponse
    {
        return $this->redirectToRoute('contao_backend_login');
    }

    /**
     * @Route("/contao/password", name="contao_backend_password")
     */
    public function passwordAction(): Response
    {
        $this->get('contao.framework')->initialize();

        $controller = new BackendPassword();

        return $controller->run();
    }

    /**
     * @Route("/contao/preview", name="contao_backend_preview")
     */
    public function previewAction(Request $request): Response
    {
        $previewScript = $this->getParameter('contao.preview_script');

        if ($request->getScriptName() !== $previewScript) {
            return $this->redirect($previewScript.$request->getRequestUri());
        }

        $this->get('contao.framework')->initialize();

        $controller = new BackendPreview();

        return $controller->run();
    }

    /**
     * @Route("/contao/confirm", name="contao_backend_confirm")
     */
    public function confirmAction(): Response
    {
        $this->get('contao.framework')->initialize();

        $controller = new BackendConfirm();

        return $controller->run();
    }

    /**
     * @Route("/contao/file", name="contao_backend_file")
     */
    public function fileAction(): Response
    {
        $this->get('contao.framework')->initialize();

        $controller = new BackendFile();

        return $controller->run();
    }

    /**
     * @Route("/contao/help", name="contao_backend_help")
     */
    public function helpAction(): Response
    {
        $this->get('contao.framework')->initialize();

        $controller = new BackendHelp();

        return $controller->run();
    }

    /**
     * @Route("/contao/page", name="contao_backend_page")
     */
    public function pageAction(): Response
    {
        $this->get('contao.framework')->initialize();

        $controller = new BackendPage();

        return $controller->run();
    }

    /**
     * @Route("/contao/popup", name="contao_backend_popup")
     */
    public function popupAction(): Response
    {
        $this->get('contao.framework')->initialize();

        $controller = new BackendPopup();

        return $controller->run();
    }

    /**
     * @Route("/contao/switch", name="contao_backend_switch")
     */
    public function switchAction(): Response
    {
        $this->get('contao.framework')->initialize();

        $controller = new BackendSwitch();

        return $controller->run();
    }

    /**
     * @Route("/contao/alerts", name="contao_backend_alerts")
     */
    public function alertsAction(): Response
    {
        $this->get('contao.framework')->initialize();

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
     * @Route("/contao/picker", name="contao_backend_picker")
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
        $picker = $this->get('contao.picker.builder')->create($config);

        if (null === $picker) {
            throw new BadRequestHttpException('Unsupported picker context');
        }

        return new RedirectResponse($picker->getCurrentUrl());
    }

    /**
     * Redirects the user to the Contao back end in case they manually call the
     * /contao/two-factor route. Will be intercepted by the two factor bundle otherwise.
     *
     * @Route("/contao/two-factor", name="contao_backend_two_factor")
     */
    public function twoFactorAuthenticationAction(): Response
    {
        return $this->redirectToRoute('contao_backend');
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        $services = parent::getSubscribedServices();

        $services['contao.framework'] = ContaoFramework::class;
        $services['contao.picker.builder'] = PickerBuilderInterface::class;

        return $services;
    }
}
