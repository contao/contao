<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\FrontendCron;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\DriverException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;

class CommandSchedulerListener
{
    /**
     * @var ContaoFrameworkInterface
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
     * @param ContaoFrameworkInterface $framework
     * @param Connection               $connection
     * @param string                   $fragmentPath
     */
    public function __construct(ContaoFrameworkInterface $framework, Connection $connection, string $fragmentPath = '_fragment')
    {
        $this->framework = $framework;
        $this->connection = $connection;
        $this->fragmentPath = $fragmentPath;
    }

    /**
     * Runs the command scheduler.
     *
     * @param PostResponseEvent $event
     */
    public function onKernelTerminate(PostResponseEvent $event): void
    {
        if (!$this->framework->isInitialized() || !$this->canRunController($event->getRequest())) {
            return;
        }

        /** @var FrontendCron $controller */
        $controller = $this->framework->createInstance(FrontendCron::class);
        $controller->run();
    }

    /**
     * Checks whether the controller can be run.
     *
     * @param Request $request
     *
     * @return bool
     */
    private function canRunController(Request $request): bool
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
     *
     * @return bool
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
