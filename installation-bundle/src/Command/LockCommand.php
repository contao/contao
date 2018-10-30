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
     * @var string
     */
    private $lockFile;

    /**
     * Construct.
     *
     * @param string $lockFile
     */
    public function __construct($lockFile)
    {
        $this->lockFile = $lockFile;

        parent::__construct();
    }

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
