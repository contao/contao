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

use Contao\CoreBundle\Monolog\ContaoContext;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class Cron
{
    /**
     * @var string[]
     */
    public const INTERVALS = ['monthly', 'weekly', 'daily', 'hourly', 'minutely'];

    /**
     * @var Connection
     */
    protected $db;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var bool
     */
    protected $debug;

    /**
     * @var array
     */
    protected $crons = [];

    public function __construct(Connection $db, LoggerInterface $logger, bool $debug)
    {
        $this->db = $db;
        $this->logger = $logger;
        $this->debug = $debug;
    }

    /**
     * Add a cron service.
     *
     * @param object $service
     */
    public function addCronJob($service, string $method, string $interval, int $priority = 0, bool $cli = false): void
    {
        if (!\in_array($interval, self::INTERVALS, true)) {
            throw new \InvalidArgumentException(sprintf('Invalid interval "%s"', $interval));
        }

        $this->crons[$interval][$priority][] = [$service, $method, $cli];
    }

    /**
     * Run the registered Contao cron jobs.
     *
     * @param bool $cli Whether the cli only crons should be run.
     */
    public function run(bool $cli = false): void
    {
        // Do not run if the last execution was less than a minute ago
        if ($this->hasToWait()) {
            return;
        }

        $currentRuns = [];

        // Store the current timestamps
        $currentTimestamps = [
            'monthly' => date('Ym'),
            'weekly' => date('YW'),
            'daily' => date('Ymd'),
            'hourly' => date('YmdH'),
            'minutely' => date('YmdHi'),
        ];

        // Get the timestamps from tl_cron
        $lastRuns = $this->db->executeQuery("SELECT * FROM tl_cron WHERE name != 'lastrun'")->fetchAll();

        foreach ($lastRuns as $lastRun) {
            $currentRuns[$lastRun['name']] = $lastRun['value'];
        }

        // Create the database entries
        foreach (self::INTERVALS as $interval) {
            if (!isset($currentRuns[$interval])) {
                $currentRuns[$interval] = 0;
                $this->db->insert('tl_cron', ['name' => $interval, 'value' => 0]);
            }
        }

        // Run the jobs
        foreach (self::INTERVALS as $interval) {
            $currentTimestamp = $currentTimestamps[$interval];

            // Skip empty intervals and jobs that have been executed already
            if (empty($this->crons[$interval]) || $currentRuns[$interval] === $currentTimestamp) {
                continue;
            }

            // Update the database before the jobs are executed, in case one of them fails
            $this->db->update('tl_cron', ['value' => $currentTimestamp], ['name' => $interval]);

            // Add a log entry if in debug mode (see #4729)
            if ($this->debug) {
                $this->logger->log(LogLevel::INFO, 'Running the '.$interval.' cron jobs', ['contao' => new ContaoContext(__METHOD__, TL_CRON)]);
            }

            // Sort the cron jobs by priority
            $crons = $this->crons[$interval];
            krsort($crons);
            $crons = array_merge(...$crons);

            foreach ($crons as $cron) {
                // Skip jobs that are only to be run on CLI, when not run via CLI
                if (!$cli && isset($cron[2]) && true === $cron[2]) {
                    continue;
                }

                $cron[0]->{$cron[1]}();
            }

            // Add a log entry if in debug mode (see #4729)
            if ($this->debug) {
                $this->logger->log(LogLevel::INFO, ucfirst($interval).' cron jobs complete', ['contao' => new ContaoContext(__METHOD__, TL_CRON)]);
            }
        }
    }

    /**
     * Check whether the last cron execution was less than a minute ago.
     */
    protected function hasToWait(int $cronTimeout = 60): bool
    {
        $return = true;

        // Get the timestamp without seconds (see #5775)
        $time = strtotime(date('Y-m-d H:i'));

        // Lock the table
        $this->db->exec('LOCK TABLES tl_cron WRITE');

        // Get the last execution date
        $cron = $this->db->executeQuery("SELECT * FROM tl_cron WHERE name = 'lastrun' LIMIT 1")->fetch();

        // Add the cron entry
        if (false === $cron) {
            $this->db->insert('tl_cron', ['name' => 'lastrun', 'value' => $time]);
            $return = false;
        }

        // Check the last execution time
        elseif ((int) $cron['value'] <= ($time - $cronTimeout)) {
            $this->db->update('tl_cron', ['value' => $time], ['name' => 'lastrun']);
            $return = false;
        }

        $this->db->exec('UNLOCK TABLES');

        return $return;
    }
}
