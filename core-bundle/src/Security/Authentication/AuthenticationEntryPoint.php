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

use Contao\CoreBundle\Exception\InsufficientAuthenticationException;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\PageError401;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class AuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var UriSigner
     */
    private $uriSigner;

    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * @var ScopeMatcher
     */
    private $scopeMatcher;

    /**
     * @internal Do not inherit from this class; decorate the "contao.security.entry_point" service instead
     */
    public function __construct(RouterInterface $router, UriSigner $uriSigner, ContaoFramework $framework, ScopeMatcher $scopeMatcher)
    {
        $this->router = $router;
        $this->uriSigner = $uriSigner;
        $this->framework = $framework;
        $this->scopeMatcher = $scopeMatcher;
    }

    public function start(Request $request, AuthenticationException $authException = null)
    {
        if ($this->scopeMatcher->isBackendRequest($request)) {
            return $this->redirectToBackend($request);
        }

        $this->framework->initialize();

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

    private function redirectToBackend(Request $request): RedirectResponse
    {
        if ($request->query->count() < 1) {
            return new RedirectResponse($this->router->generate('contao_backend_login'));
        }

        $url = $this->router->generate(
            'contao_backend_login',
            ['redirect' => $request->getUri()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return new RedirectResponse($this->uriSigner->sign($url));
    }
}
