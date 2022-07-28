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

use Contao\Config;
use Contao\CoreBundle\Cron\Cron;
use Contao\CoreBundle\Framework\ContaoFramework;
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
        private Cron $cron,
        private ContaoFramework $framework,
        private Connection $connection,
        private string $fragmentPath = '_fragment',
    ) {
    }

    /**
     * Runs the command scheduler.
     */
    public function __invoke(TerminateEvent $event): void
    {
        if ($this->framework->isInitialized() && $this->canRunCron($event->getRequest())) {
            $this->cron->run(Cron::SCOPE_WEB);
        }
    }

    private function canRunCron(Request $request): bool
    {
        $pathInfo = $request->getPathInfo();

        // Skip the listener upon fragment URLs
        if (preg_match('~(?:^|/)'.preg_quote($this->fragmentPath, '~').'/~', $pathInfo)) {
            return false;
        }

        $config = $this->framework->getAdapter(Config::class);

        return !$config->get('disableCron') && $this->canRunDbQuery();
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
