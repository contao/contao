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

use Contao\User;
use Doctrine\ORM\EntityRepository;

class TrustedDeviceRepository extends EntityRepository
{
    public function findForUser(User $user)
    {
        return $this->createQueryBuilder('td')
            ->andWhere('td.userClass = :userClass')
            ->andWhere('td.userId = :userId')
            ->setParameter('userClass', \get_class($user))
            ->setParameter('userId', (int) $user->id)

            ->getQuery()
            ->execute()
        ;
    }
}
