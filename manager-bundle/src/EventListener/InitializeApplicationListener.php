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
use Symfony\Component\Filesystem\Path;

/**
 * @internal
 */
class InitializeApplicationListener
{
    private string $projectDir;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
    }

    /**
     * Adds the initialize.php file.
     */
    public function __invoke(InitializeApplicationEvent $event): void
    {
        $filesystem = new Filesystem();
        $targetPath = Path::join($this->projectDir, 'system/initialize.php');

        if ($filesystem->exists($targetPath)) {
            return;
        }

        $filesystem->copy(__DIR__.'/../../skeleton/system/initialize.php', $targetPath, true);
    }
}
