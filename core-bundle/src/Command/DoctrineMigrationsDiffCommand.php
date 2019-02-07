<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
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
 */
class DoctrineMigrationsDiffCommand extends DiffCommand
{
    public const COMMAND_ID = 'console.command.contao_corebundle_command_doctrinemigrationsdiffcommand';

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output): ?int
    {
        /** @var Application $application */
        $application = $this->getApplication();

        DoctrineCommandHelper::setApplicationConnection($application, $input->getOption('db-configuration'));

        DoctrineCommand::configureMigrations(
            $application->getKernel()->getContainer(),
            $this->getMigrationConfiguration($input, $output)
        );

        parent::execute($input, $output);

        return 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        parent::configure();

        $this->setName('doctrine:migrations:diff');
    }
}
