<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Cron;

use Contao\CoreBundle\Entity\CronJob as CronJobEntity;
use Contao\CoreBundle\Repository\CronJobRepository;
use Cron\CronExpression;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

class Cron
{
    private const MINUTELY_CACHE_KEY = 'contao.cron.minutely_run';
    final public const SCOPE_WEB = 'web';
    final public const SCOPE_CLI = 'cli';

    /**
     * @var array<CronJob>
     */
    private array $cronJobs = [];

    /**
     * @param \Closure():CronJobRepository      $repository
     * @param \Closure():EntityManagerInterface $entityManager
     */
    public function __construct(private \Closure $repository, private \Closure $entityManager, private CacheItemPoolInterface $cachePool, private LoggerInterface|null $logger = null)
    {
    }

    public function addCronJob(CronJob $cronjob): void
    {
        $this->cronJobs[] = $cronjob;
    }

    /**
     * @return list<CronJob>
     */
    public function getCronJobs(): array
    {
        return $this->cronJobs;
    }

    public function hasMinutelyCliCron(): bool
    {
        $item = $this->cachePool->getItem(self::MINUTELY_CACHE_KEY);

        return $item->isHit();
    }

    /**
     * Run all the registered Contao cron jobs.
     */
    public function run(string $scope, bool $force = false): void
    {
        $this->doRun($this->cronJobs, $scope, $force);
    }

    /**
     * Run a single Contao cron job.
     */
    public function runJob(string $name, string $scope, bool $force = false): void
    {
        foreach ($this->cronJobs as $cronJob) {
            if ($name === $cronJob->getName()) {
                $this->doRun([$cronJob], $scope, $force);

                return;
            }
        }

        throw new \InvalidArgumentException(sprintf('Cronjob "%s" does not exist.', $name));
    }

    /**
     * @param array<CronJob> $cronJobs
     */
    private function doRun(array $cronJobs, string $scope, bool $force = false): void
    {
        // Validate scope
        if (self::SCOPE_WEB !== $scope && self::SCOPE_CLI !== $scope) {
            throw new \InvalidArgumentException('Invalid scope "'.$scope.'"');
        }

        if (self::SCOPE_CLI === $scope) {
            $cacheItem = $this->cachePool->getItem(self::MINUTELY_CACHE_KEY);
            $cacheItem->expiresAfter(60); // 60 seconds
            $this->cachePool->save($cacheItem);
        }

        /** @var CronJobRepository $repository */
        $repository = ($this->repository)();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = ($this->entityManager)();

        /** @var array<CronJob> $cronJobsToBeRun */
        $cronJobsToBeRun = [];

        $now = new \DateTimeImmutable();

        try {
            // Lock cron table
            $repository->lockTable();

            // Go through each cron job
            foreach ($cronJobs as $cron) {
                $interval = $cron->getInterval();
                $name = $cron->getName();

                // Determine the last run date
                $lastRunDate = null;

                /** @var CronJobEntity|null $lastRunEntity */
                $lastRunEntity = $repository->findOneByName($name);

                if (null !== $lastRunEntity) {
                    $lastRunDate = $lastRunEntity->getLastRun();
                } else {
                    $lastRunEntity = new CronJobEntity($name);
                    $entityManager->persist($lastRunEntity);
                }

                // Check if the cron should be run
                $expression = CronExpression::factory($interval);

                if (!$force && null !== $lastRunDate && $now < $expression->getNextRunDate($lastRunDate)) {
                    continue;
                }

                // Update the cron entry
                $lastRunEntity->setLastRun($now);

                // Add job to the cron jobs to be run
                $cronJobsToBeRun[] = $cron;
            }

            $entityManager->flush();
        } finally {
            $repository->unlockTable();
        }

        // Execute all cron jobs to be run
        foreach ($cronJobsToBeRun as $cron) {
            $this->logger?->debug(sprintf('Executing cron job "%s"', $cron->getName()));

            $cron($scope);
        }
    }
}
