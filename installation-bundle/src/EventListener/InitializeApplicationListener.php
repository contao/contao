<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\InstallationBundle\EventListener;

use Contao\InstallationBundle\Event\InitializeApplicationEvent;
use Symfony\Bundle\FrameworkBundle\Command\AssetsInstallCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class InitializeApplicationListener implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * Listens to the contao_installation.initialize event.
     */
    public function onInitialize(InitializeApplicationEvent $event): void
    {
        $this->installAssets($event);
        $this->installContao($event);
        $this->createSymlinks($event);
    }

    private function installAssets(InitializeApplicationEvent $event): void
    {
        $webDir = $this->container->getParameter('contao.web_dir');

        if (file_exists($webDir.'/bundles/contaocore/core.js')) {
            return;
        }

        $application = new Application($this->container->get('kernel'));

        $command = new AssetsInstallCommand($this->container->get('filesystem'));
        $command->setApplication($application);

        $input = new ArgvInput(['bin/console', 'assets:install', $webDir, '--symlink', '--relative']);

        if (null === ($output = $this->runCommand($command, $input))) {
            return;
        }

        $event->setOutput($output);
    }

    private function installContao(InitializeApplicationEvent $event): void
    {
        $projectDir = $this->container->getParameter('kernel.project_dir');

        if (is_dir($projectDir.'/system/config')) {
            return;
        }

        $webDir = $this->container->getParameter('contao.web_dir');
        $command = $this->container->get('contao.command.install');
        $input = new ArgvInput(['contao:install', substr($webDir, \strlen($projectDir) + 1)]);

        if (null === ($output = $this->runCommand($command, $input))) {
            return;
        }

        $event->setOutput($output);
    }

    private function createSymlinks(InitializeApplicationEvent $event): void
    {
        $webDir = $this->container->getParameter('contao.web_dir');

        if (is_link($webDir.'/system/themes')) {
            return;
        }

        $projectDir = $this->container->getParameter('kernel.project_dir');
        $command = $this->container->get('contao.command.symlinks');
        $input = new ArgvInput(['contao:symlinks', substr($webDir, \strlen($projectDir) + 1)]);

        if (null === ($output = $this->runCommand($command, $input))) {
            return;
        }

        $event->setOutput($output);
    }

    /**
     * Runs a command and returns the error (if any).
     */
    private function runCommand(Command $command, InputInterface $input): ?string
    {
        if ($command instanceof ContainerAwareInterface) {
            $command->setContainer($this->container);
        }

        $output = new BufferedOutput(OutputInterface::VERBOSITY_NORMAL, true);
        $status = $command->run($input, $output);

        if ($status > 0) {
            return $output->fetch();
        }

        return null;
    }
}
