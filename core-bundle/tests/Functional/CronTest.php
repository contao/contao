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

use Contao\CoreBundle\Cron\Cron;
use Contao\CoreBundle\Cron\CronJob;
use Contao\CoreBundle\Entity\CronJob as CronJobEntity;
use Contao\CoreBundle\Exception\CronExecutionSkippedException;
use Contao\CoreBundle\Repository\CronJobRepository;
use Contao\TestCase\FunctionalTestCase;
use GuzzleHttp\Promise\Promise;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class CronTest extends FunctionalTestCase
{
    /**
     * @dataProvider provideSkippedJobs
     */
    public function testRestoresOnlyTheSkippedJobsOwnTimestamp(bool $async, string|null $newLastRun): void
    {
        self::bootKernel();
        self::resetDatabaseSchema();

        $doctrine = self::getContainer()->get('doctrine');
        $manager = $doctrine->getManager();
        $repository = new CronJobRepository($doctrine);
        $connection = $manager->getConnection();
        $manager->persist(new CronJobEntity('test', new \DateTimeImmutable('2000-01-01')));
        $manager->flush();

        $skip = static function () use ($connection, $newLastRun): never {
            if (null !== $newLastRun) {
                $connection->update('tl_cron_job', ['lastRun' => $newLastRun], ['name' => 'test']);
            }

            throw new CronExecutionSkippedException();
        };

        $cron = new Cron(static fn () => $repository, static fn () => $manager, new ArrayAdapter());
        $cron->addCronJob(new CronJob($async ? static fn () => new Promise($skip) : $skip, '@hourly', name: 'test'));
        $cron->run(Cron::SCOPE_CLI);

        $this->assertSame($newLastRun ?? '2000-01-01 00:00:00', $connection->fetchOne('SELECT lastRun FROM tl_cron_job WHERE name = ?', ['test']));
    }

    public static function provideSkippedJobs(): iterable
    {
        yield 'synchronous skip' => [false, null];
        yield 'asynchronous skip' => [true, null];
        yield 'synchronous skip after another run' => [false, '2100-01-01 00:00:00'];
        yield 'asynchronous skip after another run' => [true, '2100-01-01 00:00:00'];
    }
}
