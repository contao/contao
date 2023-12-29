<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\Cron\Cron;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\TerminateEvent;

/**
 * @internal
 */
class CommandSchedulerListener
{
    public function __construct(
        private readonly Cron $cron,
        private readonly Connection $connection,
        private readonly string $fragmentPath = '_fragment',
        private readonly bool $autoMode = false,
    ) {
    }

    /**
     * Runs the command scheduler.
     */
    public function __invoke(TerminateEvent $event): void
    {
        if ($this->shouldRunCron($event->getRequest())) {
            $this->cron->run(Cron::SCOPE_WEB);
        }
    }

    private function shouldRunCron(Request $request): bool
    {
        $pathInfo = $request->getPathInfo();

        // Skip the listener upon fragment URLs
        if (preg_match('~(?:^|/)'.preg_quote($this->fragmentPath, '~').'/~', $pathInfo)) {
            return false;
        }

        // Without the DB table, the cron framework cannot work
        if (!$this->canRunDbQuery()) {
            return false;
        }

        if ($this->autoMode && $this->cron->hasMinutelyCliCron()) {
            return false;
        }

        return true;
    }

    /**
     * Checks if a database connection can be established and the table exist.
     */
    private function canRunDbQuery(): bool
    {
        try {
            return $this->connection->isConnected()
                && $this->connection->createSchemaManager()->tablesExist(['tl_cron_job']);
        } catch (Exception) {
            return false;
        }
    }
}
