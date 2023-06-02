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
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Filesystem\Path;

#[AsCommand(
    name: 'dot-env:remove',
    description: 'Removes a parameter from the .env file.'
)]
class RemoveDotEnvCommand extends Command
{
    private readonly string $projectDir;

    public function __construct(Application $application)
    {
        parent::__construct();

        $this->projectDir = $application->getProjectDir();
    }

    protected function configure(): void
    {
        $this->addArgument('key', InputArgument::REQUIRED, 'The variable name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dotenv = new Dotenv();
        $dotenv->usePutenv(false);

        $path = Path::join($this->projectDir, '.env');
        $dumper = new DotenvDumper($path.'.local');
        $key = $input->getArgument('key');

        if (file_exists($path) && isset($dotenv->parse(file_get_contents($path))[$key])) {
            $dumper->setParameter($key, '');
        } else {
            $dumper->unsetParameter($key);
        }

        $dumper->dump();

        return 0;
    }
}
