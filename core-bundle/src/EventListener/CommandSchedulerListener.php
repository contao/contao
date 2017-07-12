<?php

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
use Doctrine\DBAL\Exception\ConnectionException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;

/**
 * Triggers the Contao command scheduler after the response has been sent.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
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
     * Constructor.
     *
     * @param ContaoFrameworkInterface $framework
     * @param Connection               $connection
     */
    public function __construct(ContaoFrameworkInterface $framework, Connection $connection)
    {
        $this->framework = $framework;
        $this->connection = $connection;
    }

    /**
     * Runs the command scheduler.
     *
     * @param PostResponseEvent $event
     */
    public function onKernelTerminate(PostResponseEvent $event)
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
    private function canRunController(Request $request)
    {
        /** @var Config $config */
        $config = $this->framework->getAdapter(Config::class);

        return $config->isComplete()
            && !$config->get('disableCron')
            && in_array($request->attributes->get('_route'), ['contao_backend', 'contao_frontend'], true)
            && $this->canRunDbQuery()
        ;
    }

    /**
     * Checks if a database connection can be established and the table exist.
     *
     * @return bool
     */
    private function canRunDbQuery()
    {
        try {
            return $this->connection->isConnected() && $this->connection->getSchemaManager()->tablesExist(['tl_cron']);
        } catch (ConnectionException $e) {
            return false;
        }
    }
}
