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

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('dot-env:remove')
            ->setDescription('Removes a parameter from the .env file.')
            ->addArgument('key', InputArgument::REQUIRED, 'The variable name')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $file = $this->projectDir.'/.env';

        if (!file_exists($file)) {
            return;
        }

        $dotenv = new DotenvDumper($file);
        $dotenv->unsetParameter($input->getArgument('key'));
        $dotenv->dump();
    }
}
