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
use Doctrine\DBAL\Exception\DriverException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\TerminateEvent;

/**
 * @internal
 */
class CommandSchedulerListener
{
    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var string
     */
    private $fragmentPath;

    /**
     * @var Cron
     */
    private $cron;

    public function __construct(ContaoFramework $framework, Connection $connection, Cron $cron, string $fragmentPath = '_fragment')
    {
        $this->framework = $framework;
        $this->connection = $connection;
        $this->cron = $cron;
        $this->fragmentPath = $fragmentPath;
    }

    /**
     * Runs the command scheduler.
     */
    public function onKernelTerminate(TerminateEvent $event): void
    {
        if ($this->framework->isInitialized() && $this->canRunCron($event->getRequest())) {
            $this->cron->run([Cron::SCOPE_WEB]);
        }
    }

    private function canRunCron(Request $request): bool
    {
        $pathInfo = $request->getPathInfo();

        // Skip the listener in the install tool and upon fragment URLs
        if (preg_match('~(?:^|/)(?:contao/install$|'.preg_quote($this->fragmentPath, '~').'/)~', $pathInfo)) {
            return false;
        }

        /** @var Config $config */
        $config = $this->framework->getAdapter(Config::class);

        return $config->isComplete() && !$config->get('disableCron') && $this->canRunDbQuery();
    }

    /**
     * Checks if a database connection can be established and the table exist.
     */
    private function canRunDbQuery(): bool
    {
        try {
            return $this->connection->isConnected() && $this->connection->getSchemaManager()->tablesExist(['tl_cron']);
        } catch (DriverException $e) {
            return false;
        }
    }
}
