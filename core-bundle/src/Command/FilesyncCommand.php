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
use Symfony\Component\Lock\LockInterface;

/**
 * Synchronizes the file system with the database.
 */
class FilesyncCommand extends Command implements FrameworkAwareInterface
{
    use FrameworkAwareTrait;

    /**
     * @var LockInterface
     */
    private $lock;

    public function __construct(LockInterface $lock)
    {
        $this->lock = $lock;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('contao:filesync')
            ->setDescription('Synchronizes the file system with the database.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->lock->acquire()) {
            $output->writeln('The command is already running in another process.');

            return 1;
        }

        $this->framework->initialize();

        $strLog = Dbafs::syncFiles();
        $output->writeln(sprintf('Synchronization complete (see <info>%s</info>).', $strLog));

        $this->lock->release();

        return 0;
    }
}
