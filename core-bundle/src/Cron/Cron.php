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
use Contao\CoreBundle\Exception\CronExecutionSkippedException;
use Contao\CoreBundle\Repository\CronJobRepository;
use Cron\CronExpression;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\Utils;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

class Cron
{
    final public const MINUTELY_CACHE_KEY = 'contao.cron.minutely_run';

    final public const SCOPE_WEB = 'web';

    final public const SCOPE_CLI = 'cli';

    /**
     * @var array<CronJob>
     */
    private array $cronJobs = [];

    /**
     * @param \Closure(): CronJobRepository      $repository
     * @param \Closure(): EntityManagerInterface $entityManager
     */
    public function __construct(
        private readonly \Closure $repository,
        private readonly \Closure $entityManager,
        private readonly CacheItemPoolInterface $cachePool,
        private readonly LoggerInterface|null $logger = null,
    ) {
    }

    public function hasMinutelyCliCron(): bool
    {
        return $this->cachePool->getItem(self::MINUTELY_CACHE_KEY)->isHit();
    }

    public function updateMinutelyCliCron(string $scope): PromiseInterface
    {
        if (self::SCOPE_CLI !== $scope) {
            throw new CronExecutionSkippedException();
        }

        // 70 instead of 60 seconds to give some time for stale caches
        $cacheItem = $this->cachePool->getItem(self::MINUTELY_CACHE_KEY);
        $cacheItem->expiresAfter(70);

        $this->cachePool->saveDeferred($cacheItem);

        // Using a promise here not because the cache file takes forever to create but in
        // order to make sure it's one of the first cron jobs that are executed. The fact
        // that we can use deferred cache item saving is an added bonus.
        return $promise = new Promise(
            function () use (&$promise): void {
                $this->cachePool->commit();
                $promise->resolve('Saved cache item.');
            },
        );
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

        throw new \InvalidArgumentException(\sprintf('Cronjob "%s" does not exist.', $name));
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

        $repository = ($this->repository)();
        $entityManager = ($this->entityManager)();
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
                $lastRunEntity = $repository->findOneByName($name);

                if ($lastRunEntity) {
                    $lastRunDate = $lastRunEntity->getLastRun();
                } else {
                    $lastRunEntity = new CronJobEntity($name);
                    $entityManager->persist($lastRunEntity);
                }

                // Check if the cron should be run
                $expression = CronExpression::factory($interval);

                if (!$force && $lastRunDate && $now < $expression->getNextRunDate($lastRunDate)) {
                    continue;
                }

                // Store the previous run in case the cronjob skips itself
                $cron->setPreviousRun($lastRunEntity->getLastRun());

                // Update the cron entry
                $lastRunEntity->setLastRun($now);

                // Add job to the cron jobs to be run
                $cronJobsToBeRun[] = $cron;
            }

            $entityManager->flush();
        } finally {
            $repository->unlockTable();
        }

        // Callback to restore previous run date in case cronjob skips itself
        $onSkip = static function (CronJob $cron) use ($repository, $entityManager): void {
            $lastRunEntity = $repository->findOneByName($cron->getName());
            $lastRunEntity->setLastRun($cron->getPreviousRun());

            $entityManager->flush();
        };

        $this->executeCrons($cronJobsToBeRun, $scope, $onSkip);
    }

    /**
     * @param array<CronJob> $crons
     */
    private function executeCrons(array $crons, string $scope, \Closure $onSkip): void
    {
        $promises = [];
        $exception = null;

        foreach ($crons as $cron) {
            try {
                $this->logger?->debug(\sprintf('Executing cron job "%s"', $cron->getName()));

                $promise = $cron($scope);

                if (!$promise instanceof PromiseInterface) {
                    continue;
                }

                $promise->then(
                    function () use ($cron): void {
                        $this->logger?->debug(\sprintf('Asynchronous cron job "%s" finished successfully', $cron->getName()));
                    },
                    function ($reason) use ($onSkip, $cron): void {
                        if ($reason instanceof CronExecutionSkippedException) {
                            $onSkip($cron);
                        } else {
                            $this->logger?->debug(\sprintf('Asynchronous cron job "%s" failed: %s', $cron->getName(), $reason));
                        }
                    },
                );

                $promises[] = $promise;
            } catch (CronExecutionSkippedException) {
                $onSkip($cron);
            } catch (\Throwable $e) {
                // Catch any exceptions so that other cronjobs are still executed
                $this->logger?->error((string) $e);

                if (!$exception) {
                    $exception = $e;
                }
            }
        }

        if ($promises) {
            Utils::settle($promises)->wait();
        }

        // Throw the first exception
        if ($exception) {
            throw $exception;
        }
    }
}
