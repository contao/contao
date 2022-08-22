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
use Symfony\Component\Dotenv\Dotenv;

/**
 * @internal
 */
class RemoveDotEnvCommand extends Command
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
            ->setName('dot-env:remove')
            ->setDescription('Removes a parameter from the .env file.')
            ->addArgument('key', InputArgument::REQUIRED, 'The variable name')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $key = $input->getArgument('key');
        $file = $this->projectDir.'/.env';
        $fileLocal = $file.'.local';

        $dotenv = new DotenvDumper($fileLocal);

        if (file_exists($file) && isset((new Dotenv(false))->parse(file_get_contents($file))[$key])) {
            $dotenv->setParameter($key, '');
        } else {
            $dotenv->unsetParameter($key);
        }

        $dotenv->dump();

        return 0;
    }
}
