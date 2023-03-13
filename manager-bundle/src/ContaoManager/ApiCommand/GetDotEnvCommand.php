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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Filesystem\Path;

/**
 * @internal
 */
class GetDotEnvCommand extends Command
{
    protected static $defaultName = 'dot-env:get';
    protected static $defaultDescription = 'Reads a parameter from the .env file.';

    private string $projectDir;

    public function __construct(Application $application)
    {
        parent::__construct();

        $this->projectDir = $application->getProjectDir();
    }

    protected function configure(): void
    {
        $this->addArgument('key', InputArgument::OPTIONAL, 'The variable name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = Path::join($this->projectDir, '.env');

        if (!file_exists($path)) {
            return 0;
        }

        $dotenv = new Dotenv();
        $dotenv->usePutenv(false);

        $vars = [];

        foreach ([$path, $path.'.local'] as $filePath) {
            if (file_exists($filePath)) {
                $vars = array_merge($vars, $dotenv->parse(file_get_contents($filePath)));
            }
        }

        $key = $input->getArgument('key');

        if (!$key) {
            $output->write(json_encode($vars));
        }

        if (isset($vars[$key])) {
            $output->write($vars[$key]);
        }

        return 0;
    }
}
