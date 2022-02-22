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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Path;

/**
 * @internal
 */
class RemoveDotEnvCommand extends Command
{
    protected static $defaultName = 'dot-env:remove';
    protected static $defaultDescription = 'Removes a parameter from the .env file.';

    private string $projectDir;

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
        $file = Path::join($this->projectDir, '.env');

        if (!file_exists($file)) {
            return 0;
        }

        $dotenv = new DotenvDumper($file);
        $dotenv->unsetParameter($input->getArgument('key'));
        $dotenv->dump();

        return 0;
    }
}
