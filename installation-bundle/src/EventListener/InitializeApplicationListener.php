<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\InstallationBundle\EventListener;

use Contao\CoreBundle\Command\InstallCommand;
use Contao\CoreBundle\Command\SymlinksCommand;
use Contao\InstallationBundle\Event\InitializeApplicationEvent;
use Symfony\Bundle\FrameworkBundle\Command\AssetsInstallCommand;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Installs the assets, directories and symlinks.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class InitializeApplicationListener implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * Listens to the contao_installation.initialize event.
     *
     * @param InitializeApplicationEvent $event
     */
    public function onInitialize(InitializeApplicationEvent $event)
    {
        $this->installAssets($event);
        $this->installContao($event);
        $this->createSymlinks($event);
    }

    /**
     * Installs the assets.
     *
     * @param InitializeApplicationEvent $event
     */
    private function installAssets(InitializeApplicationEvent $event)
    {
        $webDir = $this->container->getParameter('contao.web_dir');

        if (file_exists($webDir.'/bundles/contaocore/core.js')) {
            return;
        }

        $application = new Application($this->container->get('kernel'));

        $command = new AssetsInstallCommand();
        $command->setApplication($application);

        $input = new ArgvInput(['assets:install', '--relative', $webDir]);

        if (null === ($output = $this->runCommand($command, $input))) {
            return;
        }

        $event->setOutput($output);
    }

    /**
     * Installs the Contao folders.
     *
     * @param InitializeApplicationEvent $event
     */
    private function installContao(InitializeApplicationEvent $event)
    {
        $projectDir = $this->container->getParameter('kernel.project_dir');

        if (is_dir($projectDir.'/system/config')) {
            return;
        }

        $command = new InstallCommand(
            $projectDir,
            $this->container->getParameter('contao.upload_path'),
            $this->container->getParameter('contao.image.target_dir')
        );

        $webDir = $this->container->getParameter('contao.web_dir');
        $input = new ArgvInput(['contao:install', '--target='.$webDir]);

        if (null === ($output = $this->runCommand($command, $input))) {
            return;
        }

        $event->setOutput($output);
    }

    /**
     * Creates the symlinks.
     *
     * @param InitializeApplicationEvent $event
     */
    private function createSymlinks(InitializeApplicationEvent $event)
    {
        $webDir = $this->container->getParameter('contao.web_dir');

        if (is_link($webDir.'/system/themes')) {
            return;
        }

        $command = new SymlinksCommand(
            $this->container->getParameter('kernel.project_dir'),
            $this->container->getParameter('contao.upload_path'),
            $this->container->getParameter('kernel.logs_dir'),
            $this->container->get('contao.resource_finder')
        );

        $input = new ArgvInput(['contao:symlinks', '--target='.$webDir]);

        if (null === ($output = $this->runCommand($command, $input))) {
            return;
        }

        $event->setOutput($output);
    }

    /**
     * Runs a command and returns the error (if any).
     *
     * @param ContainerAwareCommand $command
     * @param InputInterface        $input
     *
     * @return string|null
     */
    private function runCommand(ContainerAwareCommand $command, InputInterface $input)
    {
        $command->setContainer($this->container);

        $output = new BufferedOutput(OutputInterface::VERBOSITY_NORMAL, true);
        $status = $command->run($input, $output);

        if ($status > 0) {
            return $output->fetch();
        }

        return null;
    }
}
