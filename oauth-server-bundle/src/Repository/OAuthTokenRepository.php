<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\OAuthServerBundle\Repository;

use Contao\OAuthServerBundle\Entity\OAuthToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @template-extends ServiceEntityRepository<OAuthToken>
 *
 * @internal
 */
class OAuthTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OAuthToken::class);
    }

    public function add(OAuthToken $token): void
    {
        $this->getEntityManager()->persist($token);
        $this->getEntityManager()->flush();
    }

    public function revoke(string $type, string $identifier): void
    {
        $this->findOneBy(['type' => $type, 'identifier' => $identifier])?->revoke();
        $this->getEntityManager()->flush();
    }

    public function isRevoked(string $type, string $identifier): bool
    {
        return $this->findOneBy(['type' => $type, 'identifier' => $identifier])?->isRevoked() ?? true;
    }

    public function purgeExpired(\DateTimeImmutable $before): int
    {
        return (int) $this->createQueryBuilder('t')
            ->delete()
            ->where('t.expires < :before')
            ->setParameter('before', $before)
            ->getQuery()
            ->execute()
        ;
    }
}
