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
use Contao\ManagerBundle\Api\ManagerConfig;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'config:set',
    description: 'Sets the Contao Manager configuration from a JSON string.'
)]
class SetConfigCommand extends Command
{
    private readonly ManagerConfig $managerConfig;

    public function __construct(Application $application)
    {
        parent::__construct();

        $this->managerConfig = $application->getManagerConfig();
    }

    protected function configure(): void
    {
        $this->addArgument('json', InputArgument::REQUIRED, 'The configuration as JSON string');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $data = json_decode($input->getArgument('json'), true, 512, JSON_THROW_ON_ERROR);

        $this->managerConfig->write($data);

        return 0;
    }
}
