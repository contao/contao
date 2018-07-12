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
use Contao\BackendUser;
use Contao\CoreBundle\Picker\PickerConfig;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorToken;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

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
        $this->get('contao.framework')->initialize();

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
        $this->get('contao.framework')->initialize();

        if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            return new RedirectResponse($this->get('router')->generate('contao_backend'));
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
     * @return Response
     *
     * @Route("/contao/password", name="contao_backend_password")
     */
    public function passwordAction(): Response
    {
        $this->get('contao.framework')->initialize();

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
        $this->get('contao.framework')->initialize();

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
        $this->get('contao.framework')->initialize();

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
        $this->get('contao.framework')->initialize();

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
        $this->get('contao.framework')->initialize();

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
        $this->get('contao.framework')->initialize();

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
        $this->get('contao.framework')->initialize();

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
        $this->get('contao.framework')->initialize();

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
        $this->get('contao.framework')->initialize();

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
     * @return Response
     *
     * @Route("/contao/two-factor", name="contao_backend_two_factor")
     */
    public function twoFactorAuthenticationAction(): Response
    {
        $this->get('contao.framework')->initialize();

        $token = $this->get('security.token_storage')->getToken();

        if (!$token instanceof TwoFactorToken) {
            return $this->redirectToRoute('contao_backend_login');
        }

        $authenticatedToken = $token->getAuthenticatedToken();

        if (!$authenticatedToken instanceof UsernamePasswordToken) {
            return $this->redirectToRoute('contao_backend_login');
        }

        $user = $authenticatedToken->getUser();

        if (!$user instanceof BackendUser) {
            return $this->redirectToRoute('contao_backend_login');
        }

        return $this->loginAction();
    }

    /**
     * Redirects the user to the Contao back end in case they manually call the
     * /contao/two-factor-check route. Will be intercepted by the two factor bundle otherwise
     * and can be removed if https://github.com/scheb/two-factor-bundle/pull/145 gets merged.
     *
     * @return Response
     *
     * @Route("/contao/two-factor-check", name="contao_backend_two_factor_check")
     */
    public function twoFactorAuthenticationCheckAction(): Response
    {
        return $this->redirectToRoute('contao_backend');
    }
}
