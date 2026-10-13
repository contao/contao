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

use Contao\CoreBundle\Entity\PersonalAccessToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * @template-extends ServiceEntityRepository<PersonalAccessToken>
 *
 * @method PersonalAccessToken|null findOneById(string $id)
 *
 * @internal
 */
class PersonalAccessTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PersonalAccessToken::class);
    }

    /**
     * @return list<PersonalAccessToken>
     */
    public function getAllForUser(int $userId): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.userId = :userId')
            ->setParameter(':userId', $userId)
            ->orderBy('t.createdAt', 'desc')
            ->getQuery()
            ->execute()
        ;
    }

    public function findValidByIdAndScope(Uuid|string $id, string $scope): PersonalAccessToken|null
    {
        if (!$id instanceof Uuid) {
            $id = Uuid::fromString($id);
        }

        return $this->createQueryBuilder('t')
            ->where('t.id = :id')
            ->andWhere('t.scope = :scope')
            ->andWhere('t.expiresAt > :now OR t.expiresAt IS NULL')
            ->setParameter('id', $id, UuidType::NAME)
            ->setParameter('scope', $scope)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * Will be removed once the ModuleTwoFactor back end module is rewritten to a
     * modern backend controller.
     */
    public function remove(PersonalAccessToken $personalAccessToken): void
    {
        $this->getEntityManager()->remove($personalAccessToken);
        $this->getEntityManager()->flush();
    }
}
