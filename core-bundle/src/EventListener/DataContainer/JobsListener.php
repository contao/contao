<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Job\Owner;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Symfony\Bundle\SecurityBundle\Security;

#[AsCallback(table: 'tl_job', target: 'config.onload')]
class JobsListener
{
    public function __construct(
        private readonly Security $security,
        private readonly Connection $connection,
    ) {
    }

    public function __invoke(): void
    {
        $userIdentifier = $this->security->getUser()?->getUserIdentifier();

        $query = \sprintf('pid = 0 AND (owner = %s OR (public = %s AND owner = %s))',
            $this->connection->quote($userIdentifier),
            $this->connection->quote(true, ParameterType::BOOLEAN),
            $this->connection->quote(Owner::SYSTEM),
        );

        $GLOBALS['TL_DCA']['tl_job']['list']['sorting']['filter'][] = $query;

        /*
                $qb->andWhere('j.pid = 0'); // Only parents
                $qb->andWhere(
                    $expr->or(
                        $expr->eq('j.owner', ':userOwner'),
                        $expr->and(
                            $expr->eq('j.public', true),
                            $expr->eq('j.owner', ':systemOwner'),
                        ),
                    ),
                );
                $qb->setParameter('userOwner', $userid);
                $qb->setParameter('systemOwner', Owner::SYSTEM);
                $qb->orderBy('j.tstamp', 'DESC');
        */
    }
}
