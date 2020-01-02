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
    public function addCronJob($service, string $method, string $interval): void
    {
        $this->crons[] = [
            'service' => $service,
            'method' => $method,
            'interval' => $interval,
            'name' => \get_class($service).'::'.$method,
        ];
    }

    /**
     * Run the registered Contao cron jobs.
     */
    public function run(): void
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

        // Execute all crons to be run
        foreach ($cronsToBeRun as $cron) {
            if (null !== $this->logger) {
                $this->logger->debug(sprintf('Executing cron job "%s"', $cron['name']));
            }

            $cron['service']->{$cron['method']}();
        }
    }
}
