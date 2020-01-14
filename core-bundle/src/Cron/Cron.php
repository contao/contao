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
use Psr\Log\LoggerInterface;

class Cron
{
    public const SCOPE_WEB = 'web';
    public const SCOPE_CLI = 'cli';

    /**
     * @var CronJobRepository
     */
    private $repository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array<CronJob>
     */
    private $cronJobs = [];

    public function __construct(CronJobRepository $repository, LoggerInterface $logger = null)
    {
        $this->logger = $logger;
        $this->repository = $repository;
    }

    public function addCronJob(object $service, string $interval, string $method = null): void
    {
        $this->cronJobs[] = new CronJob($service, $interval, $method);
    }

    /**
     * Run all the registered Contao cron jobs.
     */
    public function run(string $scope): void
    {
        // Validate scope
        if (self::SCOPE_WEB !== $scope && self::SCOPE_CLI !== $scope) {
            throw new \InvalidArgumentException('Invalid scope "'.$scope.'"');
        }

        /** @var array<CronJob> */
        $cronJobsToBeRun = [];
        $now = new \DateTimeImmutable();

        try {
            // Lock cron table
            $this->repository->lockTable();

            // Go through each cron job
            foreach ($this->cronJobs as $cron) {
                $interval = $cron->getInterval();
                $name = $cron->getName();

                // Determine the last run date
                $lastRunDate = null;

                /** @var CronJobEntity $lastRunEntity */
                $lastRunEntity = $this->repository->findOneByName($name);

                if (null !== $lastRunEntity) {
                    $lastRunDate = $lastRunEntity->getLastRun();
                } else {
                    $lastRunEntity = new CronJobEntity($name);
                }

                // Check if the cron should be run
                $expression = CronExpression::factory($interval);

                if (null === $lastRunDate || $now >= $expression->getNextRunDate($lastRunDate)) {
                    // Update the cron entry
                    $lastRunEntity->setLastRun($now);
                    $this->repository->persistAndFlush($lastRunEntity);

                    // Add job to the crons to be run
                    $cronJobsToBeRun[] = $cron;
                }
            }
        } finally {
            $this->repository->unlockTable();
        }

        // Execute all crons to be run
        foreach ($cronJobsToBeRun as $cron) {
            if (null !== $this->logger) {
                $this->logger->debug(sprintf('Executing cron job "%s"', $cron->getName()));
            }

            $cron($scope);
        }
    }
}
