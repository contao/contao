<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\InstallationBundle\EventListener;

use Contao\CoreBundle\Command\InstallCommand;
use Contao\CoreBundle\Command\SymlinksCommand;
use Contao\InstallationBundle\Event\InitializeApplicationEvent;
use Symfony\Bundle\FrameworkBundle\Command\AssetsInstallCommand;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Listens to the contao_installation.initialize event.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class InitializeApplicationListener implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * Installs the assets, directories and symlinks.
     *
     * @param InitializeApplicationEvent $event
     */
    public function onInitialize(InitializeApplicationEvent $event)
    {
        $input = new ArgvInput([
            'assets:install',
            '--relative',
            $this->container->getParameter('kernel.root_dir').'/../web',
        ]);

        if (null !== ($output = $this->runCommand(new AssetsInstallCommand(), $input))) {
            $event->setOutput($output);
        }

        if (null !== ($output = $this->runCommand(new InstallCommand()))) {
            $event->setOutput($output);
        }

        if (null !== ($output = $this->runCommand(new SymlinksCommand()))) {
            $event->setOutput($output);
        }
    }

    /**
     * Runs a command and returns the error (if any).
     *
     * @param ContainerAwareCommand $command
     * @param InputInterface|null   $input
     *
     * @return string|null
     */
    private function runCommand(ContainerAwareCommand $command, InputInterface $input = null)
    {
        if (null === $input) {
            $input = new ArgvInput([]);
        }

        $command->setContainer($this->container);

        $output = new BufferedOutput(OutputInterface::VERBOSITY_NORMAL, true);
        $status = $command->run($input, $output);

        if ($status > 0) {
            return $output->fetch();
        }

        return null;
    }
}
