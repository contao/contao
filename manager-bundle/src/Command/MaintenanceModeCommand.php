<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

/**
 * @internal
 */
class MaintenanceModeCommand extends Command
{
    private string $webDir;
    private Filesystem $filesystem;

    public function __construct(string $webDir, Filesystem $filesystem = null)
    {
        $this->webDir = $webDir;
        $this->filesystem = $filesystem ?? new Filesystem();

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('contao:maintenance-mode')
            ->addArgument('state', InputArgument::OPTIONAL, 'Use "enable" to enable and "disable" to disable the maintenance mode. If the state is already the desired one, nothing happens. You can also use "on" and "off".')
            ->setDescription('Changes the state of the system maintenance mode. Without any argument, it toggles between the current state.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $isEnabled = $this->filesystem->exists(Path::join($this->webDir, 'maintenance.html'));

        switch ($input->getArgument('state')) {
            // Enable
            case 'enable':
            case 'on':
                $shouldEnable = true;
                break;

            // Disable
            case 'disable':
            case 'off':
                $shouldEnable = false;
                break;

            // Toggle if none or both options are set
            default:
                $shouldEnable = !$isEnabled;
        }

        $io = new SymfonyStyle($input, $output);

        if ($shouldEnable) {
            $this->enable($isEnabled);
            $io->success('Maintenance mode enabled.');
        } else {
            $this->disable($isEnabled);
            $io->success('Maintenance mode disabled.');
        }

        return 0;
    }

    private function enable(bool $isEnabled): void
    {
        // Already enabled
        if ($isEnabled) {
            return;
        }

        // Move existing template
        if ($this->filesystem->exists(Path::join($this->webDir, '.maintenance.html'))) {
            $this->filesystem->rename(
                Path::join($this->webDir, '.maintenance.html'),
                Path::join($this->webDir, 'maintenance.html'),
                true
            );

            return;
        }

        // Copy our own skeleton template
        $this->filesystem->copy(
            Path::makeAbsolute('../Resources/skeleton/public/.maintenance.html', __DIR__),
            Path::join($this->webDir, 'maintenance.html'),
            true
        );
    }

    private function disable(bool $isEnabled): void
    {
        // Already disabled
        if (!$isEnabled) {
            return;
        }

        $this->filesystem->rename(
            Path::join($this->webDir, 'maintenance.html'),
            Path::join($this->webDir, '.maintenance.html'),
            true
        );
    }
}
