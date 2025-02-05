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

use Contao\CoreBundle\Cron\Cron;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\FrontendShare;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\LogoutException;

/**
 * @internal
 */
#[Route(defaults: ['_scope' => 'frontend'])]
class FrontendController extends AbstractController
{
    #[Route('/_contao/cron', name: 'contao_frontend_cron')]
    public function cronAction(Request $request): Response
    {
        if ($request->isMethod(Request::METHOD_GET)) {
            $this->container->get('contao.cron')->run(Cron::SCOPE_WEB);
        }

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    #[Route('/_contao/share', name: 'contao_frontend_share')]
    public function shareAction(): RedirectResponse
    {
        $this->initializeContaoFramework();

        $controller = new FrontendShare();

        return $controller->run();
    }

    /**
     * Symfony will un-authenticate the user automatically by calling this route.
     */
    #[Route('/_contao/logout', name: 'contao_frontend_logout')]
    public function logoutAction(): never
    {
        throw new LogoutException('The user was not logged out correctly.');
    }

    /**
     * Generates a 1px transparent PNG image uncacheable response.
     *
     * This route can be used to include e.g. a hidden <img> tag to force a request to
     * the application. That way, cookies can be set even if the output is cached
     * (used in the core if the "alwaysLoadFromCache" option is enabled to evaluate
     * the RememberMe cookie and then set the session cookie).
     */
    #[Route('/_contao/check_cookies', name: 'contao_frontend_check_cookies', defaults: ['_token_check' => false])]
    public function checkCookiesAction(): Response
    {
        static $image = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

        $response = new Response(base64_decode($image, true));
        $response->setPrivate();

        $response->headers->set('Content-Type', 'image/png');
        $response->headers->addCacheControlDirective('no-store');
        $response->headers->addCacheControlDirective('must-revalidate');

        return $response;
    }

    /**
     * Returns a script that makes sure a valid request token is filled into all forms
     * if the "alwaysLoadFromCache" option is enabled.
     */
    #[Route('/_contao/request_token_script', name: 'contao_frontend_request_token_script')]
    public function requestTokenScriptAction(): Response
    {
        $tokenValue = json_encode($this->container->get('contao.csrf.token_manager')->getDefaultTokenValue(), JSON_THROW_ON_ERROR);

        $response = new Response();
        $response->setContent('document.querySelectorAll(\'input[name=REQUEST_TOKEN],input[name$="[REQUEST_TOKEN]"]\').forEach(function(i){i.value='.$tokenValue.'})');

        $response->headers->set('Content-Type', 'application/javascript; charset=UTF-8');
        $response->headers->addCacheControlDirective('no-store');
        $response->headers->addCacheControlDirective('must-revalidate');

        return $response;
    }

    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();

        $services['contao.cron'] = Cron::class;
        $services['contao.csrf.token_manager'] = ContaoCsrfTokenManager::class;

        return $services;
    }
}
