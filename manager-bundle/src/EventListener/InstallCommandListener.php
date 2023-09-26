<?php

declare(strict_types=1);

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
use Symfony\Component\Filesystem\Path;

/**
 * @internal
 */
class InstallCommandListener
{
    private string $projectDir;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
    }

    /**
     * Adds the initialize.php file.
     */
    public function __invoke(ConsoleTerminateEvent $event): void
    {
        if (!$event->getCommand() instanceof InstallCommand) {
            return;
        }

        (new Filesystem())
            ->copy(
                __DIR__.'/../../skeleton/system/initialize.php',
                Path::join($this->projectDir, 'system/initialize.php'),
                true
            )
        ;
    }
}
