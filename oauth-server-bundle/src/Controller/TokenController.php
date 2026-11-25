<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\OAuthServerBundle\Controller;

use Contao\OAuthServerBundle\AuthorizationServer\AuthorizationServerFactory;
use Contao\OAuthServerBundle\Http\PsrMessageConverter;
use Contao\OAuthServerBundle\ResourceContext;
use League\OAuth2\Server\Exception\OAuthServerException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
class TokenController
{
    public function __construct(
        private readonly AuthorizationServerFactory $serverFactory,
        private readonly PsrMessageConverter $psrMessageConverter,
        private readonly ResourceContext $context,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $resource = $request->request->get('resource');

        if (null !== $resource && !$this->context->matchesResource((string) $resource)) {
            return $this->noStore(new JsonResponse(['error' => 'invalid_target', 'error_description' => 'Unknown resource.'], Response::HTTP_BAD_REQUEST));
        }

        try {
            $psrResponse = $this->serverFactory->create()->respondToAccessTokenRequest($this->psrMessageConverter->toPsr($request), $this->psrMessageConverter->newResponse());
        } catch (OAuthServerException $e) {
            $psrResponse = $e->generateHttpResponse($this->psrMessageConverter->newResponse());
        }

        return $this->noStore($this->psrMessageConverter->toSymfony($psrResponse));
    }

    private function noStore(Response $response): Response
    {
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}
