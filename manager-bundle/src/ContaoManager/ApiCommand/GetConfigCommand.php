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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'config:get',
    description: 'Gets the Contao Manager configuration as JSON string.',
)]
class GetConfigCommand extends Command
{
    private readonly ManagerConfig $managerConfig;

    public function __construct(Application $application)
    {
        parent::__construct();

        $this->managerConfig = $application->getManagerConfig();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->write(json_encode($this->managerConfig->all(), JSON_THROW_ON_ERROR));

        return 0;
    }
}
