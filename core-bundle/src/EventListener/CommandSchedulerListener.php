<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\FrontendCron;
use Doctrine\DBAL\Connection;

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
     */
    public function onKernelTerminate()
    {
        if (!$this->framework->isInitialized() || !$this->canRunController()) {
            return;
        }

        /** @var FrontendCron $controller */
        $controller = $this->framework->createInstance('Contao\FrontendCron');
        $controller->run();
    }

    /**
     * Checks whether the controller can be run.
     *
     * @return bool
     */
    private function canRunController()
    {
        /** @var Config $config */
        $config = $this->framework->getAdapter(Config::class);

        return $config->isComplete()
            && !$config->get('disableCron')
            && $this->connection->getSchemaManager()->tablesExist(['tl_cron'])
        ;
    }
}
