<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Repository;

use Contao\CoreBundle\Entity\AccessToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\TokenNotFoundException;

/**
 * @template-extends ServiceEntityRepository<AccessToken>
 *
 * @internal
 */
class AccessTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccessToken::class);
    }

    public function findByToken(string $token): AccessToken
    {
        $qb = $this->createQueryBuilder('a');
        $qb
            ->where('a.token = :token')
            ->setParameter('token', $token)
        ;

        $rows = $qb->getQuery()->getResult();

        if (0 === \count($rows)) {
            throw new TokenNotFoundException('No token found');
        }

        return $rows[0];
    }

    public function persist(AccessToken ...$entities): void
    {
        foreach ($entities as $entity) {
            $this->_em->persist($entity);
        }

        $this->_em->flush();
    }

    public function removeExpired(): void
    {
        $qb = $this->_em->createQueryBuilder();
        $qb
            ->delete($this->_entityName, 'a')
            ->where('a.expiresAt <= :now')
            ->setParameter('now', new \DateTimeImmutable())
        ;

        $qb->getQuery()->execute();
    }
}
