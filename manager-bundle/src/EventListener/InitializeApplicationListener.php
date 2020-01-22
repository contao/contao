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

use Contao\InstallationBundle\Event\InitializeApplicationEvent;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @internal
 */
class InitializeApplicationListener
{
    /**
     * @var string
     */
    private $projectDir;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
    }

    /**
     * Adds the initialize.php file.
     */
    public function __invoke(InitializeApplicationEvent $event): void
    {
        if (file_exists($this->projectDir.'/system/initialize.php')) {
            return;
        }

        (new Filesystem())
            ->copy(
                __DIR__.'/../Resources/skeleton/system/initialize.php',
                $this->projectDir.'/system/initialize.php',
                true
            )
        ;
    }
}
