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

use Contao\CoreBundle\Security\User\ContaoUserProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class AccessTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(
        private readonly AccessTokenManager $accessTokenManager,
        private readonly ContaoUserProvider $backendUserProvider,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function getUserBadgeFrom(string $token): UserBadge
    {
        if (!$personalAccessToken = $this->accessTokenManager->getValidPersonalAccessToken($token)) {
            throw new BadCredentialsException('Invalid access token.');
        }

        $user = $this->backendUserProvider->loadUserById($personalAccessToken->getUserId());

        $personalAccessToken->setLastUsed(new \DateTimeImmutable());

        $this->entityManager->persist($personalAccessToken);
        $this->entityManager->flush();

        return new UserBadge($user->getUserIdentifier());
    }
}
