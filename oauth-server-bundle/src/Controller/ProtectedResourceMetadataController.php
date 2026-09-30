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

use Contao\OAuthServerBundle\ResourceContext;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @internal
 */
class ProtectedResourceMetadataController
{
    public function __construct(private readonly ResourceContext $context)
    {
    }

    public function __invoke(string|null $path = null): JsonResponse
    {
        if (null !== $path && '/'.trim($path, '/') !== $this->context->getResourcePath()) {
            throw new NotFoundHttpException();
        }

        return new JsonResponse([
            'resource' => $this->context->getResource(),
            'authorization_servers' => [$this->context->getIssuer()],
            'scopes_supported' => $this->context->getScopes(),
            'bearer_methods_supported' => ['header'],
            'resource_name' => $this->context->getResourceName(),
        ]);
    }
}
