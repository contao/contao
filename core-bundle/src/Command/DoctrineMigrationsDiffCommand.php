<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Command;

use Doctrine\Bundle\MigrationsBundle\Command\DoctrineCommand;
use Doctrine\Bundle\MigrationsBundle\Command\Helper\DoctrineCommandHelper;
use Doctrine\DBAL\Migrations\Tools\Console\Command\DiffCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This Command replaces Doctrine\Bundle\MigrationsBundle\Command\MigrationsDiffDoctrineCommand
 * if ORM is not installed. The regular doctrine:migrations:diff command only works with ORM.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class DoctrineMigrationsDiffCommand extends DiffCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('doctrine:migrations:diff');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Application $application */
        $application = $this->getApplication();

        DoctrineCommandHelper::setApplicationConnection($application, $input->getOption('db-configuration'));

        DoctrineCommand::configureMigrations(
            $application->getKernel()->getContainer(),
            $this->getMigrationConfiguration($input, $output)
        );

        parent::execute($input, $output);
    }
}
