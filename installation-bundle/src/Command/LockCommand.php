<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\InstallationBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Locks the install tool.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class LockCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('contao:install:lock')
            ->setDescription('Locks the install tool.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $file = $this->getContainer()->getParameter('kernel.project_dir').'/var/install_lock';

        if (file_exists($file)) {
            $output->writeln('<comment>The install tool has been locked already.</comment>');

            return 1;
        }

        $fs = new Filesystem();
        $fs->dumpFile($file, 3);

        $output->writeln('<info>The install tool has been locked.</info>');

        return 0;
    }
}
