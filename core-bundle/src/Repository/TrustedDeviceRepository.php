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

use Contao\BackendUser;
use Contao\FrontendUser;
use Doctrine\ORM\EntityRepository;

class TrustedDeviceRepository extends EntityRepository
{
    public function findForBackendUser(BackendUser $user)
    {
        return $this->createQueryBuilder('td')
            ->andWhere('td.user = :user')
            ->setParameter('user', (int) $user->id)

            ->getQuery()
            ->execute()
        ;
    }

    public function findForFrontendUser(FrontendUser $user)
    {
        return $this->createQueryBuilder('td')
            ->andWhere('td.member = :member')
            ->setParameter('member', (int) $user->id)

            ->getQuery()
            ->execute()
        ;
    }
}
