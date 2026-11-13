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
     * @param \Closure():CronJobRepository      $repository
     * @param \Closure():EntityManagerInterface $entityManager
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
        if (preg_match('/[^\x00-\x7F]/', $name)) {
            trigger_deprecation('contao/core-bundle', '5.3', 'Using non-ASCII characters in cron job names is deprecated and will no longer work in Contao 7.');

            $name = preg_replace_callback('/[^\x00-\x7F]+/', static fn (array $match): string => bin2hex($match[0]), $name);
        }

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

        $now = new \DateTime();

        $jobIds = [];

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
                $id = $repository->createJob($name, $now);

                // Successfully created the new cron job entity
                if (null !== $id) {
                    $cron->setPreviousRun($now);
                    $jobIds[spl_object_id($cron)] = $id;
                    $cronJobsToBeRun[] = $cron;

                    continue;
                }

                // Race condition: another process created the same entity at the same time

                if (!$force) {
                    continue;
                }

                $lastRunEntity = $repository->findOneByName($name);

                if (null === $lastRunEntity) {
                    throw new \LogicException(\sprintf('Cron job "%s" does not exist after a unique constraint violation.', $name));
                }
            }

            // Check if the cron should be run
            $expression = new CronExpression($interval);

            if (!$force && $lastRunDate && $now < $expression->getNextRunDate($lastRunDate)) {
                continue;
            }

            // Update the cron entry
            $updated = $repository->updateLastRun($lastRunEntity->getId(), $now, $force ? null : $lastRunEntity->getLastRun());

            // If the update was not successful, skip execution
            if (!$updated && !$force) {
                continue;
            }

            // Store the previous run in case the cronjob skips itself
            $cron->setPreviousRun($lastRunEntity->getLastRun());
            $jobIds[spl_object_id($cron)] = $lastRunEntity->getId();

            // Add job to the cron jobs to be run
            $cronJobsToBeRun[] = $cron;
        }

        $repository->purgeOldRecords(array_map(static fn (CronJob $cronJob): string => $cronJob->getName(), $cronJobs));

        // Callback to restore previous run date in case cronjob skips itself
        $onSkip = static function (CronJob $cron) use ($repository, $jobIds, $now): void {
            $repository->updateLastRun($jobIds[spl_object_id($cron)], $cron->getPreviousRun(), $now);
        };

        $this->executeCrons($cronJobsToBeRun, $scope, $entityManager, $onSkip);
    }

    /**
     * @param array<CronJob> $crons
     */
    private function executeCrons(array $crons, string $scope, EntityManagerInterface $entityManager, \Closure $onSkip): void
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
            // Close the DB connection until async promises have completed.
            $entityManager->getConnection()->close();

            Utils::settle($promises)->wait();
        }

        // Throw the first exception
        if ($exception) {
            throw $exception;
        }
    }
}
