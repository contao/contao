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

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Migration\MigrationCollection;
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\InstallationBundle\Database\Installer;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

class MigrateCommand extends Command
{
    /**
     * @var MigrationCollection
     */
    private $migrations;

    /**
     * @var FileLocator
     */
    private $fileLocator;

    /**
     * @var string
     */
    private $projectDir;

    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * @var ?Installer
     */
    private $installer;

    /**
     * @var SymfonyStyle
     */
    private $io;

    public function __construct(MigrationCollection $migrations, FileLocator $fileLocator, string $projectDir, ContaoFramework $framework, Installer $installer = null)
    {
        $this->migrations = $migrations;
        $this->fileLocator = $fileLocator;
        $this->projectDir = $projectDir;
        $this->framework = $framework;
        $this->installer = $installer;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('contao:migrate')
            ->addOption('with-deletes', null, InputOption::VALUE_NONE, 'Execute all database migrations including DROP queries. Can be used together with --no-interaction.')
            ->addOption('schema-only', null, InputOption::VALUE_NONE, 'Execute database schema migration only.')
            ->setDescription('Executes migrations and the database schema diff.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        if ($input->getOption('schema-only')) {
            return $this->executeSchemaDiff($input->getOption('with-deletes')) ? 0 : 1;
        }

        if (!$this->executeMigrations()) {
            return 1;
        }

        if (!$this->executeSchemaDiff($input->getOption('with-deletes'))) {
            return 1;
        }

        if (!$this->executeMigrations()) {
            return 1;
        }

        $this->io->success('All migrations completed.');

        return 0;
    }

    private function executeMigrations(): bool
    {
        while (true) {
            $first = true;

            foreach ($this->migrations->getPendingNames() as $migration) {
                if ($first) {
                    $this->io->section('Pending migrations');
                    $first = false;
                }

                $this->io->writeln(' * '.$migration);
            }

            $runOnceFiles = $this->getRunOnceFiles();

            if ($runOnceFiles) {
                @trigger_error('Using runonce.php files has been deprecated and will no longer work in Contao 5.0. Use the migration framework instead.', E_USER_DEPRECATED);
            }

            foreach ($runOnceFiles as $file) {
                if ($first) {
                    $this->io->section('Pending migrations');
                    $first = false;
                }

                $this->io->writeln(' * Runonce file: '.$file);
            }

            if ($first) {
                break;
            }

            if (!$this->io->confirm('Execute the listed migrations?')) {
                return false;
            }

            $this->io->section('Execute migrations');

            $count = 0;

            /** @var MigrationResult $result */
            foreach ($this->migrations->run() as $result) {
                ++$count;

                $this->io->writeln(' * '.$result->getMessage());

                if (!$result->isSuccessful()) {
                    $this->io->error('Migration failed');
                }
            }

            foreach ($this->getRunOnceFiles() as $file) {
                ++$count;

                $this->executeRunonceFile($file);
                $this->io->writeln(' * Executed runonce file: '.$file);
            }

            $this->io->success('Executed '.$count.' migrations.');
        }

        return true;
    }

    private function getRunOnceFiles(): array
    {
        try {
            $files = $this->fileLocator->locate('config/runonce.php', null, false);
        } catch (FileLocatorFileNotFoundException $e) {
            return [];
        }

        return array_map(
            function ($path) {
                return rtrim((new Filesystem())->makePathRelative($path, $this->projectDir), '/');
            },
            $files
        );
    }

    private function executeRunonceFile(string $file): void
    {
        $this->framework->initialize();

        include $this->projectDir.'/'.$file;

        (new Filesystem())->remove($this->projectDir.'/'.$file);
    }

    private function executeSchemaDiff(bool $withDeletesOption): bool
    {
        if (null === $this->installer) {
            $this->io->error('Service "contao.installer" not found. The installation bundle needs to be installed in order to execute schema diff migrations.');

            return false;
        }

        $commandsByHash = [];

        while (true) {
            $this->installer->compileCommands();

            if (!$commands = $this->installer->getCommands()) {
                return true;
            }

            $hasNewCommands = \count(
                array_filter(
                    array_keys(array_merge(...array_values($commands))),
                    static function ($hash) use ($commandsByHash) {
                        return !isset($commandsByHash[$hash]);
                    }
                )
            );

            if (!$hasNewCommands) {
                return true;
            }

            $this->io->section('Pending database migrations');

            $commandsByHash = array_merge(...array_values($commands));

            $this->io->listing($commandsByHash);

            $options = $withDeletesOption
                ? ['yes, with deletes', 'no']
                : ['yes', 'yes, with deletes', 'no'];

            $answer = $this->io->choice('Execute the listed database updates?', $options, $options[0]);

            if ('no' === $answer) {
                return false;
            }

            $this->io->section('Execute database migrations');

            $count = 0;

            foreach ($this->getCommandHashes($commands, 'yes, with deletes' === $answer) as $hash) {
                $this->io->writeln(' * '.$commandsByHash[$hash]);
                $this->installer->execCommand($hash);
                ++$count;
            }

            $this->io->success('Executed '.$count.' SQL queries.');
        }

        return true;
    }

    private function getCommandHashes(array $commands, bool $withDrops): array
    {
        if (!$withDrops) {
            unset($commands['ALTER_DROP']);

            foreach ($commands as $category => $commandsByHash) {
                foreach ($commandsByHash as $hash => $command) {
                    if ('DROP' === $category && false === strpos($command, 'DROP INDEX')) {
                        unset($commands[$category][$hash]);
                    }
                }
            }
        }

        return \count($commands) ? array_keys(array_merge(...array_values($commands))) : [];
    }
}
