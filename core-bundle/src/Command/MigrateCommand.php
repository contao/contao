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

use Contao\CoreBundle\Migration\Migrations;
use Contao\CoreBundle\Util\PackageUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrateCommand extends Command
{
    /**
     * @var Migrations
     */
    private $migrations;

    /**
     * @var SymfonyStyle
     */
    private $io;

    public function __construct(Migrations $migrations)
    {
        $this->migrations = $migrations;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('contao:migrate')
            ->setDescription('Executes migrations and the database schema diff.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        if (!$this->executeMigrations()) {
            return 1;
        }

        if (!$this->executeSchemaDiff()) {
            return 1;
        }

        if (!$this->executeMigrations()) {
            return 1;
        }

        return 0;
    }

    private function executeMigrations(): bool
    {
        while (true) {

            $first = true;

            foreach ($this->migrations->getPendingMigrations() as $migration) {
                if ($first) {
                    $this->io->section('Pending migrations');
                    $first = false;
                }

                $this->io->writeln(' * '.$migration);
            }

            if ($first) {
                break;
            }

            $this->io->section('Execute migrations');

            if (!$this->io->confirm('Execute the listed migrations?')) {
                return false;
            }

            foreach ($this->migrations->runMigrations() as $result) {
                $this->io->writeln(' * '.$result->getMessage());
            }
        }

        return true;
    }

    private function executeSchemaDiff(): bool
    {
        return true;
    }
}
