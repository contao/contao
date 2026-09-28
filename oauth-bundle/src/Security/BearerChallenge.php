<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\OAuthBundle\Security;

use Contao\OAuthBundle\ResourceContext;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
class BearerChallenge
{
    public function __construct(private readonly ResourceContext $context)
    {
    }

    public function unauthorized(string|null $error = null, string|null $description = null): Response
    {
        $params = array_filter([
            'resource_metadata' => $this->context->getProtectedResourceMetadataUrl(),
            'scope' => implode(' ', $this->context->getScopes()),
            'error' => $error,
            'error_description' => $description,
        ]);

        $header = 'Bearer '.implode(', ', array_map(
            static fn (string $k, string $v): string => \sprintf('%s="%s"', $k, addcslashes($v, '"\\')),
            array_keys($params),
            $params,
        ));

        $response = new JsonResponse(['error' => $error ?? 'unauthorized'], Response::HTTP_UNAUTHORIZED);
        $response->headers->set('WWW-Authenticate', $header);

        return $response;
    }
}
