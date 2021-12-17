<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Controller\FrontendModule;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\ManagerBundle\HttpKernel\JwtManager;
use Contao\ModuleModel;
use Contao\StringUtil;
use Contao\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class MaintenanceLoginController extends AbstractFrontendModuleController
{
    private RequestStack $requestStack;
    private ContaoCsrfTokenManager $csrfTokenManager;
    private ?JwtManager $jwtManager;

    /**
     * @internal Do not inherit from this class; decorate the "Contao\ManagerBundle\Controller\MaintenanceLoginController" service instead
     */
    public function __construct(RequestStack $requestStack, ContaoCsrfTokenManager $csrfTokenManager, JwtManager $jwtManager = null)
    {
        $this->requestStack = $requestStack;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->jwtManager = $jwtManager;
    }

    protected function getResponse(Template $template, ModuleModel $model, Request $request): ?Response
    {
        $isPreview = $this->requestStack->getMainRequest()->attributes->get('_preview', false);

        // Only show login/logout in maintenance mode and if debug is not enabled
        if (!$isPreview && (null === $this->jwtManager || !$this->isMaintenanceEnabled())) {
            return new Response('');
        }

        $formId = 'tl_maintenance_login_'.$model->id;

        $template->formId = $formId;
        $template->requestToken = $this->csrfTokenManager->getFrontendTokenValue();
        $template->disabled = null === $this->jwtManager;
        $template->invalidLogin = false;

        if ($this->isLoggedIn($request)) {
            if ($request->request->get('FORM_SUBMIT') === $formId) {
                $this->logout($request);
            }

            $template->logout = true;

            return $template->getResponse();
        }

        if ($request->request->get('FORM_SUBMIT') === $formId) {
            if ($response = $this->login($request, $model)) {
                return $response;
            }

            $template->invalidLogin = true;
        }

        $template->username = StringUtil::specialchars($request->request->get('username', ''));

        return $template->getResponse();
    }

    private function isMaintenanceEnabled(): bool
    {
        $pageModel = $this->getPageModel();

        if (null === $pageModel) {
            return false;
        }

        return (bool) $pageModel->loadDetails()->maintenanceMode;
    }

    private function isLoggedIn(Request $request): bool
    {
        if (null === $this->jwtManager) {
            return false;
        }

        $data = $this->jwtManager->parseRequest($request);

        return (bool) ($data['bypass_maintenance'] ?? false);
    }

    private function login(Request $request, ModuleModel $model): ?Response
    {
        if (
            $request->request->get('username') === $model->maintenanceUsername
            && $request->request->get('password') === $model->maintenancePassword
        ) {
            $data = $this->jwtManager->parseRequest($request);

            $response = new RedirectResponse($request->getUri());
            $this->jwtManager->addResponseCookie($response, [
                'bypass_maintenance' => true,
                'debug' => $data['debug'] ?? false,
            ]);

            return $response;
        }

        return null;
    }

    private function logout(Request $request): void
    {
        $response = new RedirectResponse($request->getUri());
        $this->jwtManager->clearResponseCookie($response);

        throw new ResponseException($response);
    }
}
