<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Api\Command;

use Contao\ManagerBundle\Api\ManagerConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GetConfigCommand extends Command
{
    /**
     * @var ManagerConfig
     */
    private $managerConfig;

    /**
     * @param ManagerConfig $managerConfig
     */
    public function __construct(ManagerConfig $managerConfig)
    {
        parent::__construct();

        $this->managerConfig = $managerConfig;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('config:get')
            ->setDescription('Gets the Contao Manager configuration as JSON string.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $output->writeln(json_encode($this->managerConfig->all()));
    }
}
