<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Security\Authentication;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class AuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    use TargetPathTrait;

    private RouterInterface $router;
    private UriSigner $uriSigner;
    private ScopeMatcher $scopeMatcher;

    /**
     * @internal
     */
    public function __construct(RouterInterface $router, UriSigner $uriSigner, ScopeMatcher $scopeMatcher)
    {
        $this->router = $router;
        $this->uriSigner = $uriSigner;
        $this->scopeMatcher = $scopeMatcher;
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        if ($this->scopeMatcher->isBackendRequest($request)) {
            return $this->redirectToBackend($request);
        }

        throw new UnauthorizedHttpException('', 'Not authorized');
    }

    private function redirectToBackend(Request $request): Response
    {
        // No redirect parameter required if the 'contao_backend' route was requested
        // without any parameters.
        if ('contao_backend' === $request->attributes->get('_route') && [] === $request->query->all()) {
            $loginParams = [];
        } else {
            $loginParams = ['redirect' => $request->getUri()];
        }

        $location = $this->router->generate(
            'contao_backend_login',
            $loginParams,
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        // No URL signing required if we do not have any parameters
        if ([] !== $loginParams) {
            $location = $this->uriSigner->sign($location);
        }

        // Our back end login controller will redirect based on the 'redirect' parameter,
        // ignoring Symfony's target path session value. Thus we remove the session variable
        // here in order to not send an unnecessary session cookie.
        if ($request->hasSession()) {
            $this->removeTargetPath($request->getSession(), 'contao_backend');
        }

        if ($request->isXmlHttpRequest()) {
            return new Response($location, 401, ['X-Ajax-Location' => $location]);
        }

        return new RedirectResponse($location);
    }
}
