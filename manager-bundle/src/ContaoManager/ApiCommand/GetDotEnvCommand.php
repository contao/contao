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

/**
 * @internal
 */
class GetDotEnvCommand extends Command
{
    /**
     * @var string
     */
    private $projectDir;

    public function __construct(Application $application)
    {
        parent::__construct();

        $this->projectDir = $application->getProjectDir();
    }

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('dot-env:get')
            ->setDescription('Reads a parameter from the .env file.')
            ->addArgument('key', InputArgument::OPTIONAL, 'The variable name')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = $this->projectDir.'/.env';
        $pathLocal = $path.'.local';

        if (!file_exists($path) && !file_exists($pathLocal)) {
            return 0;
        }

        $vars = [];

        foreach ([$path, $pathLocal] as $filePath) {
            if (file_exists($filePath)) {
                $vars = array_merge($vars, (new Dotenv(false))->parse(file_get_contents($filePath)));
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
