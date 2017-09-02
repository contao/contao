<?php

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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class GetConfigCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('config:get')
            ->setDescription('Gets the Contao Manager configuration as JSON data.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $application = $this->getApplication();

        if (!$application instanceof Application) {
            throw new \RuntimeException('');
        }

        $output->writeln(
            json_encode(
                $application->getManagerConfig()->all()
            )
        );
    }
}
