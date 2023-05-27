<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\ContaoManager\ApiCommand;

use Contao\ManagerBundle\Api\Application;
use Contao\ManagerBundle\Dotenv\DotenvDumper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

#[AsCommand(
    name: 'dot-env:set',
    description: 'Writes a parameter to the .env file.'
)]
class SetDotEnvCommand extends Command
{
    private string $projectDir;

    public function __construct(Application $application)
    {
        parent::__construct();

        $this->projectDir = $application->getProjectDir();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('key', InputArgument::REQUIRED, 'The variable name')
            ->addArgument('value', InputArgument::REQUIRED, 'The new value')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $filesystem = new Filesystem();
        $path = Path::join($this->projectDir, '.env');
        $localPath = $path.'.local';

        // Get the realpath in case it is a symlink (see #6066)
        if ($realpath = realpath($localPath)) {
            $localPath = $realpath;
        }

        $dumper = new DotenvDumper($localPath, $filesystem);
        $dumper->setParameter($input->getArgument('key'), $input->getArgument('value'));
        $dumper->dump();

        if (!file_exists($path)) {
            $filesystem->touch($path);
        }

        return 0;
    }
}
