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
use Contao\CoreBundle\Routing\ScopeMatcher;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\TerminateEvent;

/**
 * @internal
 */
#[AsEventListener]
class CommandSchedulerListener
{
    final public const REQUEST_ATTRIBUTE_ENABLE = '_contao_command_scheduler';

    public function __construct(
        private readonly Cron $cron,
        private readonly Connection $connection,
        private readonly ScopeMatcher $scopeMatcher,
        private readonly string $fragmentPath = '_fragment',
        private readonly bool $autoMode = false,
    ) {
    }

    /**
     * Runs the command scheduler.
     */
    public function __invoke(TerminateEvent $event): void
    {
        if ($this->shouldRunCron($event)) {
            $this->cron->run(Cron::SCOPE_WEB);
        }
    }

    private function shouldRunCron(TerminateEvent $event): bool
    {
        $request = $event->getRequest();
        $pathInfo = $request->getPathInfo();

        // Skip the listener upon fragment URLs
        if (preg_match('~(?:^|/)'.preg_quote($this->fragmentPath, '~').'/~', $pathInfo)) {
            return false;
        }

        if (
            (
                !$this->scopeMatcher->isContaoMainRequest($event)
                && true !== $request->attributes->get(self::REQUEST_ATTRIBUTE_ENABLE)
            )
            || false === $request->attributes->get(self::REQUEST_ATTRIBUTE_ENABLE)
        ) {
            return false;
        }

        if ($this->autoMode && $this->cron->hasMinutelyCliCron()) {
            return false;
        }

        // Without the DB table, the cron framework cannot work
        if (!$this->canRunDbQuery()) {
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
