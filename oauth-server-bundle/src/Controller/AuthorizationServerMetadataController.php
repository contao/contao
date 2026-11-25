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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @internal
 */
class AuthorizationServerMetadataController
{
    public function __construct(
        private readonly ResourceContext $context,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        return new JsonResponse([
            'issuer' => $this->context->getIssuer(),
            'authorization_endpoint' => $this->urlGenerator->generate('contao_oauth_server_authorize', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'token_endpoint' => $this->urlGenerator->generate('contao_oauth_server_token', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'response_types_supported' => ['code'],
            'grant_types_supported' => ['authorization_code', 'refresh_token'],
            'code_challenge_methods_supported' => ['S256'],
            'token_endpoint_auth_methods_supported' => ['none'],
            'scopes_supported' => $this->context->getScopes(),
            'client_id_metadata_document_supported' => true,
            'authorization_response_iss_parameter_supported' => true,
        ]);
    }
}
