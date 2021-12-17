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
use Contao\PageModel;
use Contao\StringUtil;
use Contao\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class MaintenanceLoginController extends AbstractFrontendModuleController
{
    private RequestStack $requestStack;
    private ContaoCsrfTokenManager $csrfTokenManager;
    private TranslatorInterface $translator;
    private ?JwtManager $jwtManager;

    public function __construct(RequestStack $requestStack, ContaoCsrfTokenManager $csrfTokenManager, TranslatorInterface $translator, JwtManager $jwtManager = null)
    {
        $this->requestStack = $requestStack;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->translator = $translator;
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

        if ($this->isLoggedIn($request)) {
            if ($request->request->get('FORM_SUBMIT') === $formId) {
                $this->logout($request);
            }

            $template->logout = true;

            return $template->getResponse();
        }

        if ($request->request->get('FORM_SUBMIT') === $formId) {
            $this->login($request, $model, $template);
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

        $rootPage = PageModel::findByPk($pageModel->rootId);

        if (null === $rootPage) {
            return false;
        }

        return (bool) $rootPage->maintenanceMode;
    }

    private function isLoggedIn(Request $request): bool
    {
        if (null === $this->jwtManager) {
            return false;
        }

        $data = $this->jwtManager->parseRequest($request);

        return (bool) ($data['bypass_maintenance'] ?? false);
    }

    private function login(Request $request, ModuleModel $model, Template $template): void
    {
        if (
            $request->request->get('username') === $model->maintenanceUsername
            && $request->request->get('password') === $model->maintenancePassword
        ) {
            $data = $this->jwtManager->parseRequest($request);

            $response = new RedirectResponse($request->getUri());
            $this->jwtManager->addResponseCookie($response, ['bypass_maintenance' => true, 'debug' => $data['debug'] ?? false]);

            throw new ResponseException($response);
        }

        $template->message = $this->translator->trans('ERR.invalidLogin', [], 'contao_default');
    }

    private function logout(Request $request): void
    {
        $response = new RedirectResponse($request->getUri());
        $this->jwtManager->clearResponseCookie($response);

        throw new ResponseException($response);
    }
}
