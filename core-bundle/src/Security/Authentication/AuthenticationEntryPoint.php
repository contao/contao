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
     * @param HttpUtils       $httpUtils
     * @param RouterInterface $router
     */
    public function __construct(HttpUtils $httpUtils, RouterInterface $router)
    {
        $this->httpUtils = $httpUtils;
        $this->router = $router;
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
            ['referer' => base64_encode($request->getQueryString())],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return $this->httpUtils->createRedirectResponse($request, $url);
    }
}
