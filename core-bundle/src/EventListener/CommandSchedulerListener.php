<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\FrontendCron;

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
     * Constructor.
     *
     * @param ContaoFrameworkInterface $framework The Contao framework service
     */
    public function __construct(ContaoFrameworkInterface $framework)
    {
        $this->framework = $framework;
    }

    /**
     * Runs the command scheduler.
     */
    public function onKernelTerminate()
    {
        if (!$this->framework->isInitialized()) {
            return;
        }

        /** @var Config $config */
        $config = $this->framework->getAdapter('Contao\Config');

        if ($config->get('disableCron')) {
            return;
        }

        /** @var FrontendCron $controller */
        $controller = $this->framework->createInstance('Contao\FrontendCron');
        $controller->run();
    }
}
