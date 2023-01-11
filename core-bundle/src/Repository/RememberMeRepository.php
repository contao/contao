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

use Contao\CoreBundle\Entity\RememberMe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\TokenNotFoundException;

/**
 * @template-extends ServiceEntityRepository<RememberMe>
 *
 * @internal
 */
class RememberMeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RememberMe::class);
    }

    public function findBySeries(string $series): RememberMe
    {
        $qb = $this->createQueryBuilder('rm');
        $qb
            ->where('rm.series = :series')
            ->setParameter('series', $series)
            ->orderBy('rm.lastUsed', 'ASC')
        ;

        $rows = $qb->getQuery()->getResult();

        if (0 === \count($rows)) {
            throw new TokenNotFoundException('No token found');
        }

        return $rows[0];
    }

    public function deleteBySeries(string $series): void
    {
        $qb = $this->_em->createQueryBuilder();
        $qb
            ->delete($this->_entityName, 'rm')
            ->where('rm.series = :series')
            ->setParameter('series', $series)
        ;

        $qb->getQuery()->execute();
    }

    public function persist(RememberMe ...$entities): void
    {
        foreach ($entities as $entity) {
            $this->_em->persist($entity);
        }

        $this->_em->flush();
    }
}
