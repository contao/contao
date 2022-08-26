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
 * Unlocks the install tool.
 *
 * @internal
 */
class UnlockCommand extends Command
{
    protected static $defaultName = 'contao:install:unlock';
    protected static $defaultDescription = 'Unlocks the install tool.';

    private string $lockFile;

    public function __construct(string $lockFile)
    {
        $this->lockFile = $lockFile;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!file_exists($this->lockFile)) {
            $output->writeln('<comment>The install tool was not locked.</comment>');

            return 1;
        }

        $fs = new Filesystem();
        $fs->remove($this->lockFile);

        $output->writeln('<info>The install tool has been unlocked.</info>');

        return 0;
    }
}
