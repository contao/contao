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

use Contao\BackendUser;
use Contao\CoreBundle\Entity\PersonalAccessToken;
use Contao\CoreBundle\Repository\PersonalAccessTokenRepository;
use Contao\StringUtil;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class AccessTokenManager
{
    private const string TOKEN_PREFIX = 'ct_pat_';

    public function __construct(
        private readonly PasswordHasherFactoryInterface $passwordHasherFactory,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Creates a personal access token, persists it to the database and returns the
     * token with the plain token set on the entity.
     */
    public function createToken(BackendUser $user, string $name, \DateTimeImmutable|null $expiresAt = null): PersonalAccessToken
    {
        $passwordHasher = $this->passwordHasherFactory->getPasswordHasher($user);

        $plainSecret = StringUtil::encodeBase32(random_bytes(16));

        $personalAccessToken = new PersonalAccessToken((int) $user->id, $name, $passwordHasher->hash($plainSecret), $expiresAt);

        $this->entityManager->persist($personalAccessToken);
        $this->entityManager->flush();

        $personalAccessToken->setPlainToken(\sprintf('%s%s_%s', self::TOKEN_PREFIX, $personalAccessToken->getId()->toBase32(), $plainSecret));

        return $personalAccessToken;
    }

    /**
     * Parses a plain authorization token and extracts the token ID and token secret.
     */
    public function parseToken(string $token): array|null
    {
        if (!str_starts_with($token, self::TOKEN_PREFIX)) {
            return null;
        }

        $parts = explode('_', $token);

        if (4 !== \count($parts)) {
            return null;
        }

        return [
            'id' => $parts[2],
            'secret' => $parts[3],
        ];
    }

    /**
     * Returns a valid personal access token from the given plain token.
     */
    public function getValidPersonalAccessToken(string $token): PersonalAccessToken|null
    {
        if (!$parsedToken = $this->parseToken($token)) {
            return null;
        }

        /** @var PersonalAccessTokenRepository $personalAccessTokenRepository */
        $personalAccessTokenRepository = $this->entityManager->getRepository(PersonalAccessToken::class);

        if (!$personalAccessToken = $personalAccessTokenRepository->findOneValidById($parsedToken['id'])) {
            return null;
        }

        $passwordHasher = $this->passwordHasherFactory->getPasswordHasher(BackendUser::class);

        if (!$passwordHasher->verify($personalAccessToken->getSecret(), $parsedToken['secret'])) {
            return null;
        }

        return $personalAccessToken;
    }
}
