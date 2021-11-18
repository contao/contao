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
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * @internal
 */
class CommandSchedulerListener implements ServiceSubscriberInterface
{
    private ContainerInterface $locator;
    private ContaoFramework $framework;
    private Connection $connection;
    private string $fragmentPath;

    public function __construct(ContainerInterface $locator, ContaoFramework $framework, Connection $connection, string $fragmentPath = '_fragment')
    {
        $this->locator = $locator;
        $this->framework = $framework;
        $this->connection = $connection;
        $this->fragmentPath = $fragmentPath;
    }

    /**
     * Runs the command scheduler.
     */
    public function __invoke(TerminateEvent $event): void
    {
        if ($this->framework->isInitialized() && $this->canRunCron($event->getRequest())) {
            $this->locator->get(Cron::class)->run(Cron::SCOPE_WEB);
        }
    }

    /**
     * Lazy-load services to prevent issues with MySQL server_version.
     *
     * @see https://github.com/contao/contao/pull/3623
     *
     * @return array<string>
     */
    public static function getSubscribedServices(): array
    {
        return [Cron::class];
    }

    private function canRunCron(Request $request): bool
    {
        $pathInfo = $request->getPathInfo();

        // Skip the listener in the install tool and upon fragment URLs
        if (preg_match('~(?:^|/)(?:contao/install$|'.preg_quote($this->fragmentPath, '~').'/)~', $pathInfo)) {
            return false;
        }

        $config = $this->framework->getAdapter(Config::class);

        return $config->isComplete() && !$config->get('disableCron') && $this->canRunDbQuery();
    }

    /**
     * Checks if a database connection can be established and the table exist.
     */
    private function canRunDbQuery(): bool
    {
        try {
            return $this->connection->isConnected()
                && $this->connection->createSchemaManager()->tablesExist(['tl_cron_job']);
        } catch (Exception $e) {
            return false;
        }
    }
}
