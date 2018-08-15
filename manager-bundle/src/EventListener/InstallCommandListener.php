<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\EventListener;

use Contao\CoreBundle\Command\InstallCommand;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Adds a custom initialize.php file upon contao:install.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class InstallCommandListener
{
    /**
     * @var string
     */
    private $rootDir;

    /**
     * Constructor.
     *
     * @param string $rootDir
     */
    public function __construct($rootDir)
    {
        $this->rootDir = $rootDir;
    }

    /**
     * Adds the initialize.php file.
     *
     * @param ConsoleTerminateEvent $event
     */
    public function onConsoleTerminate(ConsoleTerminateEvent $event)
    {
        if (!$event->getCommand() instanceof InstallCommand) {
            return;
        }

        (new Filesystem())
            ->copy(__DIR__.'/../Resources/system/initialize.php', $this->rootDir.'/system/initialize.php', true)
        ;
    }
}
