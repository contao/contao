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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\HttpUtils;

class AuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    /**
     * @var HttpUtils
     */
    private $httpUtils;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var UriSigner
     */
    private $uriSigner;

    /**
     * @internal Do not inherit from this class; decorate the "contao.security.entry_point" service instead
     */
    public function __construct(HttpUtils $httpUtils, RouterInterface $router, UriSigner $uriSigner)
    {
        $this->httpUtils = $httpUtils;
        $this->router = $router;
        $this->uriSigner = $uriSigner;
    }

    /**
     * {@inheritdoc}
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        if ($request->query->count() < 1) {
            return $this->httpUtils->createRedirectResponse($request, 'contao_backend_login');
        }

        $url = $this->router->generate(
            'contao_backend_login',
            ['redirect' => $request->getUri()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return $this->httpUtils->createRedirectResponse($request, $this->uriSigner->sign($url));
    }
}
