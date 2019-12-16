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

use Contao\CoreBundle\Entity\Cron as CronEntity;
use Contao\CoreBundle\Repository\CronRepository;
use Cron\CronExpression;
use Psr\Log\LoggerInterface;

class Cron
{
    /**
     * @var string
     */
    public const SCOPE_WEB = 'web';

    /**
     * @var string
     */
    public const SCOPE_CLI = 'cli';

    /**
     * @var CronRepository
     */
    private $repository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array
     */
    private $crons = [];

    public function __construct(CronRepository $repository, LoggerInterface $logger = null)
    {
        $this->logger = $logger;
        $this->repository = $repository;
    }

    /**
     * Add a cron service.
     *
     * @param string $interval The interval as a CRON expression
     */
    public function addCronJob($service, string $method, string $interval, int $priority = 0, string $scope = null): void
    {
        $this->crons[] = [
            'service' => $service,
            'method' => $method,
            'interval' => $interval,
            'priority' => $priority,
            'scope' => $scope,
            'name' => \get_class($service).'::'.$method,
        ];
    }

    /**
     * Run the registered Contao cron jobs.
     *
     * @param array $scopes Scopes of cron jobs to be run
     */
    public function run(array $scopes = []): void
    {
        $cronsToBeRun = [];
        $now = new \DateTime();

        try {
            // Lock cron table
            $this->repository->lockTable();

            // Go through each cron job
            foreach ($this->crons as $cron) {
                $interval = $cron['interval'];
                $name = $cron['name'];

                // Determine the last run date
                $lastRunDate = null;

                /** @var CronEntity $lastRunEntity */
                $lastRunEntity = $this->repository->findOneByName($name);

                if (null !== $lastRunEntity) {
                    $lastRunDate = $lastRunEntity->getLastRun();
                } else {
                    $lastRunEntity = new CronEntity($name);
                }

                // Check if the cron should be run
                $expression = CronExpression::factory($interval);

                if (null === $lastRunDate || $now >= $expression->getNextRunDate($lastRunDate)) {
                    // Skip jobs that are not to be run in the current scopes
                    if (!empty($scopes) && null !== $cron['scope'] && !\in_array($cron['scope'], $scopes, true)) {
                        if (null !== $this->logger) {
                            $this->logger->debug(sprintf('Skipping cron job "%s" for scope [%s]', $name, implode(',', $scopes)));
                        }
                        continue;
                    }

                    // Update the cron entry
                    $lastRunEntity->setLastRun($now);
                    $this->repository->persist($lastRunEntity);

                    // Add job to the crons to be run
                    $cronsToBeRun[] = $cron;
                }
            }
        } finally {
            $this->repository->unlockTable();
        }

        // Sort the crons by priority
        usort($cronsToBeRun, static function ($a, $b) {
            return $b['priority'] - $a['priority'];
        });

        // Execute all crons to be run
        foreach ($cronsToBeRun as $cron) {
            if (null !== $this->logger) {
                $this->logger->debug(sprintf('Executing cron job "%s"', $cron['name']));
            }

            $cron['service']->{$cron['method']}();
        }
    }
}
