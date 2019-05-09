<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\InstallationBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Locks the install tool.
 */
class LockCommand extends Command
{
    /**
     * @var string
     */
    private $lockFile;

    public function __construct(string $lockFile)
    {
        $this->lockFile = $lockFile;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('contao:install:lock')
            ->setDescription('Locks the install tool.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (file_exists($this->lockFile)) {
            $output->writeln('<comment>The install tool has been locked already.</comment>');

            return 1;
        }

        $fs = new Filesystem();
        $fs->dumpFile($this->lockFile, 3);

        $output->writeln('<info>The install tool has been locked.</info>');

        return 0;
    }
}
