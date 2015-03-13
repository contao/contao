<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Command;

use Contao\Dbafs;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\LockHandler;

/**
 * Synchronizes the file system with the database.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class FilesyncCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('contao:filesync')
            ->setDescription('Synchronizes the file system with the database')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $lock = new LockHandler('contao:filesync');

        // Set the lock
        if (!$lock->lock()) {
            $output->writeln('The command is already running in another process.');

            return 1;
        }

        // Run
        $strLog = Dbafs::syncFiles();
        $output->writeln("Synchronization complete (see <info>$strLog</info>).");

        // Release the lock
        $lock->release();

        return 0;
    }
}
