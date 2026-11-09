<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Functional;

use Contao\CoreBundle\Entity\CronJob;
use Contao\CoreBundle\Exception\LockAcquireTimeoutException;
use Contao\CoreBundle\Repository\CronJobRepository;
use Contao\TestCase\FunctionalTestCase;
use Doctrine\DBAL\DriverManager;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class CronJobRepositoryTest extends FunctionalTestCase
{
    public function testKeepsLockDuringFlush(): void
    {
        self::bootKernel();
        self::resetDatabaseSchema();

        $doctrine = self::getContainer()->get('doctrine');
        $manager = $doctrine->getManager();
        $repository = new CronJobRepository($doctrine);
        $connection = DriverManager::getConnection($doctrine->getConnection()->getParams());

        $registry = $this->createMock(ManagerRegistry::class);
        $registry
            ->method('getConnection')
            ->willReturn($connection)
        ;

        $registry
            ->method('getManagerForClass')
            ->willReturn($manager)
        ;

        $otherRepository = new CronJobRepository($registry);

        $manager->persist(new CronJob('test'));
        $repository->lockTable();

        try {
            $manager->flush();

            $this->expectException(LockAcquireTimeoutException::class);
            $otherRepository->lockTable();
        } finally {
            $otherRepository->unlockTable();
            $connection->close();
            $repository->unlockTable();
        }
    }
}
