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

class AuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    private RouterInterface $router;
    private UriSigner $uriSigner;
    private ScopeMatcher $scopeMatcher;

    /**
     * @internal Do not inherit from this class; decorate the "contao.security.authentication_entry_point" service instead
     */
    public function __construct(RouterInterface $router, UriSigner $uriSigner, ScopeMatcher $scopeMatcher)
    {
        $this->router = $router;
        $this->uriSigner = $uriSigner;
        $this->scopeMatcher = $scopeMatcher;
    }

    public function start(Request $request, AuthenticationException $authException = null)
    {
        if ($this->scopeMatcher->isBackendRequest($request)) {
            return $this->redirectToBackend($request);
        }

        throw new UnauthorizedHttpException('', 'Not authorized');
    }

    private function redirectToBackend(Request $request): Response
    {
        $url = $this->router->generate(
            'contao_backend_login',
            ['redirect' => $request->getUri()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $location = $this->uriSigner->sign($url);

        if ($request->isXmlHttpRequest()) {
            return new Response($location, 401, ['X-Ajax-Location' => $location]);
        }

        return new RedirectResponse($location, 302);
    }
}
