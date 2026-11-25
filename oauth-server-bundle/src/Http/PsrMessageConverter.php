<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\OAuthServerBundle\Http;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
class PsrMessageConverter
{
    private readonly Psr17Factory $psr17;
    private readonly PsrHttpFactory $psrFactory;
    private readonly HttpFoundationFactory $foundationFactory;

    public function __construct()
    {
        $this->psr17 = new Psr17Factory();
        $this->psrFactory = new PsrHttpFactory($this->psr17, $this->psr17, $this->psr17, $this->psr17);
        $this->foundationFactory = new HttpFoundationFactory();
    }

    public function toPsr(Request $request): ServerRequestInterface
    {
        return $this->psrFactory->createRequest($request);
    }

    public function newResponse(): ResponseInterface
    {
        return $this->psr17->createResponse();
    }

    public function toSymfony(ResponseInterface $response): Response
    {
        return $this->foundationFactory->createResponse($response);
    }
}
