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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @internal
 */
class SetConfigCommand extends Command
{
    /**
     * @var ManagerConfig
     */
    private $managerConfig;

    public function __construct(Application $application)
    {
        parent::__construct();

        $this->managerConfig = $application->getManagerConfig();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('config:set')
            ->setDescription('Sets the Contao Manager configuration from a JSON string.')
            ->addArgument('json', InputArgument::REQUIRED, 'The configuration as JSON string')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $data = @json_decode($input->getArgument('json'), true);

        if (null === $data) {
            throw new \RuntimeException('Invalid JSON: '.json_last_error_msg());
        }

        $this->managerConfig->write($data);

        return 0;
    }
}
