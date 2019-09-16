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
use Contao\CoreBundle\Exception\InsufficientAuthenticationException;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\FrontendIndex;
use Contao\FrontendShare;
use Contao\PageError401;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\LogoutException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @Route(defaults={"_scope" = "frontend", "_token_check" = true})
 *
 * @internal
 */
class FrontendController extends AbstractController
{
    public function indexAction(): Response
    {
        $this->initializeContaoFramework();

        $controller = new FrontendIndex();

        return $controller->run();
    }

    /**
     * @Route("/_contao/cron", name="contao_frontend_cron")
     */
    public function cronAction(Request $request, Cron $cron): Response
    {
        if ($request->isMethod(Request::METHOD_GET)) {
            $cron->run([Cron::SCOPE_WEB]);
        }

        return new Response('', 204);
    }

    /**
     * @Route("/_contao/share", name="contao_frontend_share")
     */
    public function shareAction(): RedirectResponse
    {
        $this->initializeContaoFramework();

        $controller = new FrontendShare();

        return $controller->run();
    }

    /**
     * Symfony will authenticate the user automatically by calling this route.
     *
     * @return RedirectResponse|Response
     *
     * @Route("/_contao/login", name="contao_frontend_login")
     */
    public function loginAction(): Response
    {
        $this->initializeContaoFramework();

        if (!isset($GLOBALS['TL_PTY']['error_401']) || !class_exists($GLOBALS['TL_PTY']['error_401'])) {
            throw new UnauthorizedHttpException('', 'Not authorized');
        }

        /** @var PageError401 $pageHandler */
        $pageHandler = new $GLOBALS['TL_PTY']['error_401']();

        try {
            return $pageHandler->getResponse();
        } catch (ResponseException $e) {
            return $e->getResponse();
        } catch (InsufficientAuthenticationException $e) {
            throw new UnauthorizedHttpException('', $e->getMessage());
        }
    }

    /**
     * Symfony will un-authenticate the user automatically by calling this route.
     *
     * @throws LogoutException
     *
     * @Route("/_contao/logout", name="contao_frontend_logout")
     */
    public function logoutAction(): void
    {
        throw new LogoutException('The user was not logged out correctly.');
    }

    /**
     * Generates a 1px transparent PNG image uncacheable response.
     *
     * This route can be used to include e.g. a hidden <img> tag to force
     * a request to the application. That way, cookies can be set even if
     * the output is cached (used in the core for the RememberMe cookie if
     * the "alwaysLoadFromCache" option is enabled).
     *
     * @Route("/_contao/check_cookies", name="contao_frontend_check_cookies")
     */
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
     * Returns a script that makes sure a valid request token is filled into
     * all forms if the "alwaysLoadFromCache" option is enabled.
     *
     * @Route("/_contao/request_token_script", name="contao_frontend_request_token_script")
     */
    public function requestTokenScriptAction(): Response
    {
        $token = $this
            ->get('contao.csrf.token_manager')
            ->getToken($this->getParameter('contao.csrf_token_name'))
            ->getValue()
        ;

        $token = json_encode($token);

        $response = new Response();
        $response->setContent('document.querySelectorAll("input[name=REQUEST_TOKEN]").forEach(function(i){i.value='.$token.'})');
        $response->headers->set('Content-Type', 'application/javascript; charset=UTF-8');
        $response->headers->addCacheControlDirective('no-store');
        $response->headers->addCacheControlDirective('must-revalidate');

        return $response;
    }

    /**
     * Redirects the user to the Contao front end in case they manually call the
     * /_contao/two-factor route. Will be intercepted by the two factor bundle otherwise.
     *
     * @Route("/_contao/two-factor", name="contao_frontend_two_factor")
     */
    public function twoFactorAuthenticationAction(): Response
    {
        $this->initializeContaoFramework();

        if (!isset($GLOBALS['TL_PTY']['error_401']) || !class_exists($GLOBALS['TL_PTY']['error_401'])) {
            throw new UnauthorizedHttpException('', 'Not authorized');
        }

        /** @var PageError401 $pageHandler */
        $pageHandler = new $GLOBALS['TL_PTY']['error_401']();

        try {
            return $pageHandler->getResponse();
        } catch (ResponseException $e) {
            return $e->getResponse();
        } catch (InsufficientAuthenticationException $e) {
            throw new UnauthorizedHttpException('', $e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();

        $services['contao.csrf.token_manager'] = CsrfTokenManagerInterface::class;

        return $services;
    }
}
