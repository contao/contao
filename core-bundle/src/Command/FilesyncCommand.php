<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Command;

use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\CoreBundle\Framework\FrameworkAwareTrait;
use Contao\Dbafs;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Synchronizes the file system with the database.
 *
 * @internal
 */
class FilesyncCommand extends Command implements FrameworkAwareInterface
{
    use FrameworkAwareTrait;

    protected function configure(): void
    {
        $this
            ->setName('contao:filesync')
            ->setDescription('Synchronizes the file system with the database.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->framework->initialize();

        $strLog = Dbafs::syncFiles();
        $output->writeln(sprintf('Synchronization complete (see <info>%s</info>).', $strLog));

        return 0;
    }
}
