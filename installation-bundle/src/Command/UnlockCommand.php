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
 * Unlocks the install tool.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class UnlockCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('contao:install:unlock')
            ->setDescription('Unlocks the install tool.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $file = $this->getContainer()->getParameter('kernel.project_dir').'/var/install_lock';

        if (!file_exists($file)) {
            $output->writeln('<comment>The install tool was not locked.</comment>');

            return 1;
        }

        $fs = new Filesystem();
        $fs->remove($file);

        $output->writeln('<info>The install tool has been unlocked.</info>');

        return 0;
    }
}
