<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Security\AccessToken;

use Contao\BackendUser;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\Entity\PersonalAccessToken;
use Contao\StringUtil;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class AccessTokenManager
{
    public const string TOKEN_PREFIX = 'ct_pat_';

    public function __construct(
        private readonly PasswordHasherFactoryInterface $passwordHasherFactory,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Creates a personal access token, persists it to the database and returns the
     * plain token.
     */
    public function createBackendToken(BackendUser $user, string $name, \DateTimeImmutable|null $expiresAt = null): string
    {
        return $this->createToken(ContaoCoreBundle::SCOPE_BACKEND, (int) $user->id, $name, $expiresAt);
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

    public function verifySecret(string $hashedSecret, #[\SensitiveParameter] string $plainSecret): bool
    {
        $passwordHasher = $this->passwordHasherFactory->getPasswordHasher(PersonalAccessToken::class);

        return $passwordHasher->verify($hashedSecret, $plainSecret);
    }

    private function createToken(string $scope, int $userId, string $name, \DateTimeImmutable|null $expiresAt = null): string
    {
        $plainSecret = StringUtil::encodeBase32(random_bytes(16));
        $passwordHasher = $this->passwordHasherFactory->getPasswordHasher(PersonalAccessToken::class);
        $personalAccessToken = new PersonalAccessToken($scope, $userId, $name, $passwordHasher->hash($plainSecret), $expiresAt);

        $this->entityManager->persist($personalAccessToken);
        $this->entityManager->flush();

        return \sprintf('%s%s_%s', self::TOKEN_PREFIX, $personalAccessToken->getId()->toBase32(), $plainSecret);
    }
}
