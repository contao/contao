<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\OAuthBundle\AuthorizationServer\Repository;

use Contao\OAuthBundle\AuthorizationServer\Entity\AuthCode;
use Contao\OAuthBundle\Entity\OAuthToken;
use Contao\OAuthBundle\Repository\OAuthTokenRepository;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;

/**
 * @internal
 */
class AuthCodeRepository implements AuthCodeRepositoryInterface
{
    public function __construct(private readonly OAuthTokenRepository $tokenRepository)
    {
    }

    public function getNewAuthCode(): AuthCodeEntityInterface
    {
        return new AuthCode();
    }

    public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity): void
    {
        $this->tokenRepository->add(new OAuthToken(
            OAuthToken::TYPE_CODE,
            $authCodeEntity->getIdentifier(),
            $authCodeEntity->getExpiryDateTime(),
            $authCodeEntity->getClient()->getIdentifier(),
            $authCodeEntity->getUserIdentifier(),
        ));
    }

    public function revokeAuthCode(string $codeId): void
    {
        $this->tokenRepository->revoke(OAuthToken::TYPE_CODE, $codeId);
    }

    public function isAuthCodeRevoked(string $codeId): bool
    {
        return $this->tokenRepository->isRevoked(OAuthToken::TYPE_CODE, $codeId);
    }
}
