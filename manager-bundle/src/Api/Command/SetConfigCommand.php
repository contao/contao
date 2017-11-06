<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\Api\Command;

use Contao\ManagerBundle\Api\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SetConfigCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('config:set')
            ->setDescription('Sets the Contao Manager configuration from a JSON string.')
            ->setDefinition(
                [
                    new InputArgument('json', InputArgument::REQUIRED, 'The configuration as JSON string'),
                ]
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $application = $this->getApplication();

        if (!$application instanceof Application) {
            throw new \RuntimeException('The application has not been set');
        }

        $data = @json_decode($input->getArgument('json'), true);

        if (null === $data) {
            throw new \RuntimeException('Invalid JSON: '.json_last_error_msg());
        }

        $application->getManagerConfig()->write($data);
    }
}
