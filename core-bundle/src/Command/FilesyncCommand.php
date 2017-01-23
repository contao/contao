<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Command;

use Contao\CoreBundle\Framework\FrameworkAwareTrait;
use Contao\Dbafs;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Synchronizes the file system with the database.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class FilesyncCommand extends AbstractLockedCommand
{
    use FrameworkAwareTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('contao:filesync')
            ->setDescription('Synchronizes the file system with the database.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeLocked(InputInterface $input, OutputInterface $output)
    {
        $this->framework->initialize();

        $strLog = Dbafs::syncFiles();
        $output->writeln(sprintf('Synchronization complete (see <info>%s</info>).', $strLog));

        return 0;
    }
}
