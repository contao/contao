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

use Contao\CoreBundle\Migration\DatabaseMigrationChecks;
use Contao\CoreBundle\Migration\DatabaseMigrationRunner;
use Contao\CoreBundle\Migration\UnexpectedPendingMigrationException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'contao:migrate',
    description: 'Executes migrations and updates the database schema.',
)]
class MigrateCommand extends Command
{
    private SymfonyStyle|null $io = null;

    public function __construct(
        private readonly DatabaseMigrationRunner $runner,
        private readonly DatabaseMigrationChecks $checks,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('with-deletes', null, InputOption::VALUE_NONE, 'Execute all database migrations including DROP queries. Can be used together with --no-interaction.')
            ->addOption('schema-only', null, InputOption::VALUE_NONE, 'Only update the database schema.')
            ->addOption('migrations-only', null, InputOption::VALUE_NONE, 'Only execute the migrations.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show pending migrations and schema updates without executing them.')
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'The output format (txt, ndjson)', 'txt')
            ->addOption('no-backup', null, InputOption::VALUE_NONE, 'Disable the database backup which is created by default before executing the migrations.')
            ->addOption('hash', null, InputOption::VALUE_REQUIRED, 'A hash value from a --dry-run result')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $asJson = 'ndjson' === $input->getOption('format');

        if (!\in_array($input->getOption('format'), ['txt', 'ndjson'], true)) {
            throw new InvalidOptionException(\sprintf('Unsupported format "%s".', $input->getOption('format')));
        }

        if ($asJson && !$input->getOption('dry-run') && $input->isInteractive()) {
            throw new InvalidOptionException('Use --no-interaction or --dry-run together with --format=ndjson');
        }

        try {
            if ($errors = $this->checks->compileConfigurationErrors()) {
                if ($asJson) {
                    foreach ($errors as $message) {
                        $this->writeNdjson('problem', ['message' => $message]);
                    }
                } else {
                    foreach ($errors as $error) {
                        $this->io->block($error, '!', 'fg=yellow', ' ', true);
                    }

                    $this->io->error('The database server is not configured properly. Please resolve the above issue(s) and rerun the command.');
                }

                return Command::FAILURE;
            }

            if (!$input->getOption('dry-run') && !$input->getOption('no-backup') && !$this->backup($input)) {
                return Command::FAILURE;
            }

            return $this->executeCommand($input);
        } catch (\Throwable $exception) {
            if (!$asJson) {
                throw $exception;
            }

            $this->writeNdjson('error', [
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ]);
        }

        return Command::FAILURE;
    }

    private function backup(InputInterface $input): bool
    {
        $asJson = 'ndjson' === $input->getOption('format');
        $skipDropStatements = !$input->isInteractive() && !$input->getOption('with-deletes');

        // Return early if there is no work to be done
        if (!$this->runner->hasWorkToDo($skipDropStatements)) {
            if (!$asJson) {
                $this->io->info('Database dump skipped because there are no migrations to execute.');
            }

            return true;
        }

        $config = $this->runner->createBackupConfig();

        if (!$asJson) {
            $this->io->info(\sprintf(
                'Creating a database dump to "%s" with the default options. Use --no-backup to disable this feature.',
                $config->getBackup()->getFilename(),
            ));
        }

        try {
            $config = $this->runner->createBackup($config);

            if ($asJson) {
                $this->writeNdjson('backup-result', $config);
            }

            return true;
        } catch (\Throwable $exception) {
            if ($asJson) {
                $this->writeNdjson('error', [
                    'message' => $exception->getMessage(),
                    'code' => $exception->getCode(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => $exception->getTraceAsString(),
                ]);
            } else {
                $this->io->error($exception->getMessage());
            }

            return false;
        }
    }

    private function executeCommand(InputInterface $input): int
    {
        $dryRun = (bool) $input->getOption('dry-run');
        $asJson = 'ndjson' === $input->getOption('format');
        $specifiedHash = $input->getOption('hash');

        if (!\in_array($input->getOption('format'), ['txt', 'ndjson'], true)) {
            throw new InvalidOptionException(\sprintf('Unsupported format "%s".', $input->getOption('format')));
        }

        if ($asJson && !$dryRun && $input->isInteractive()) {
            throw new InvalidOptionException('Use --no-interaction or --dry-run together with --format=ndjson');
        }

        if (!$this->validateDatabaseVersion($asJson)) {
            return 1;
        }

        if ($input->getOption('migrations-only')) {
            if ($input->getOption('schema-only')) {
                throw new InvalidOptionException('--migrations-only cannot be combined with --schema-only');
            }

            if ($input->getOption('with-deletes')) {
                throw new InvalidOptionException('--migrations-only cannot be combined with --with-deletes');
            }

            return $this->executeMigrations($dryRun, $asJson, $specifiedHash) ? 0 : 1;
        }

        if ($input->getOption('schema-only')) {
            return $this->executeSchemaDiff($dryRun, $asJson, $input->getOption('with-deletes'), $specifiedHash) ? 0 : 1;
        }

        if (!$this->executeMigrations($dryRun, $asJson, $specifiedHash)) {
            return Command::FAILURE;
        }

        if (!$this->executeSchemaDiff($dryRun, $asJson, $input->getOption('with-deletes'), $specifiedHash)) {
            return Command::FAILURE;
        }

        if (!$dryRun && null === $specifiedHash && !$this->executeMigrations($dryRun, $asJson)) {
            return Command::FAILURE;
        }

        if (!$asJson) {
            $this->io->success('All migrations completed.');
        }

        return Command::SUCCESS;
    }

    private function executeMigrations(bool &$dryRun, bool $asJson, string|null $specifiedHash = null): bool
    {
        $loopControl = 19;

        while (true) {
            $first = true;
            $migrationLabels = [];

            foreach ($this->runner->getPendingMigrationNames() as $migration) {
                if ($first) {
                    if (!$asJson) {
                        $this->io->section('Pending migrations');
                    }

                    $first = false;
                }

                $migrationLabels[] = $migration;

                if (!$asJson) {
                    $this->io->writeln(' * '.$migration);
                }
            }

            $actualHash = hash('sha256', json_encode($migrationLabels, JSON_THROW_ON_ERROR));

            if ($asJson) {
                $this->writeNdjson('migration-pending', ['names' => $migrationLabels, 'hash' => $actualHash]);
            }

            if ($first || $dryRun) {
                break;
            }

            if (null !== $specifiedHash && $specifiedHash !== $actualHash) {
                throw new InvalidOptionException(\sprintf('Specified hash "%s" does not match the actual hash "%s"', $specifiedHash, $actualHash));
            }

            if (!$asJson) {
                if (!$this->io->confirm('Execute the listed migrations?')) {
                    return false;
                }

                $this->io->section('Execute migrations');
            }

            $count = 0;

            try {
                foreach ($this->runner->runMigrations($migrationLabels) as $result) {
                    ++$count;

                    if ($asJson) {
                        $this->writeNdjson('migration-result', [
                            'message' => $result->getMessage(),
                            'isSuccessful' => $result->isSuccessful(),
                        ]);
                    } else {
                        $this->io->writeln(' * '.$result->getMessage());

                        if (!$result->isSuccessful()) {
                            $this->io->error('Migration failed');
                        }
                    }
                }

                if (!$asJson) {
                    $this->io->success("Executed $count migrations.");
                }
            } catch (UnexpectedPendingMigrationException $exception) {
                if ($asJson) {
                    $this->writeNdjson('migration-result', [
                        'message' => $exception->getMessage(),
                        'isSuccessful' => false,
                    ]);
                } else {
                    $this->io->success("Executed $count migrations.");
                    $this->io->error("{$exception->getMessage()}\nRestarting migration process...");
                }
            }

            if (null !== $specifiedHash) {
                // Do not run the schema update after migrations got executed if a hash was specified,
                // because that hash could never match both, migrations and schema updates
                $dryRun = true;

                // Do not run the update recursive if a hash was specified
                break;
            }

            if ($loopControl-- < 1) {
                if ($asJson) {
                    $this->writeNdjson('error', [
                        'message' => 'The migrations were stopped after 19 iterations as a precaution. There is a high chance of an infinite loop of migrations.',
                        'isSuccessful' => false,
                    ]);
                } else {
                    $this->io->error('The migrations were stopped after 19 iterations as a precaution. There is a high chance of an infinite loop of migrations. If this is not the case, please re-run the command. To troubleshoot this error, check the shouldRun() method of the migration(s) listed above.');
                }

                return false;
            }
        }

        return true;
    }

    private function executeSchemaDiff(bool $dryRun, bool $asJson, bool $withDeletesOption, string|null $specifiedHash = null): bool
    {
        if ($warnings = [...$this->checks->compileConfigurationWarnings(), ...$this->checks->compileSchemaWarnings(!$withDeletesOption)]) {
            if ($asJson) {
                foreach ($warnings as $message) {
                    $this->writeNdjson('warning', ['message' => $message]);
                }
            } else {
                $this->io->warning(implode("\n\n", $warnings));

                if (!$this->io->confirm('Continue regardless of the warnings?')) {
                    return false;
                }
            }
        }

        $lastCommands = [];

        while (true) {
            $commands = $this->runner->compileSchemaCommands();

            $hasNewCommands = [] !== array_diff($commands, $lastCommands);
            $lastCommands = $commands;

            // Backwards compatibility with doctrine/dbal < 4.5.0, see
            // https://github.com/doctrine/dbal/pull/7302
            $sortedCommands = $commands;
            sort($sortedCommands);

            $commandsHash = hash('sha256', json_encode($sortedCommands, JSON_THROW_ON_ERROR));

            if ($asJson) {
                $this->writeNdjson('schema-pending', [
                    'commands' => $commands,
                    'hash' => $commandsHash,
                ]);
            }

            if (!$hasNewCommands) {
                return true;
            }

            if (!$asJson) {
                $this->io->section("Pending database migrations ($commandsHash)");
                $this->io->listing($commands);
            }

            if ($dryRun) {
                return true;
            }

            if (null !== $specifiedHash && $specifiedHash !== $commandsHash) {
                throw new InvalidOptionException(\sprintf('Specified hash "%s" does not match the actual hash "%s"', $specifiedHash, $commandsHash));
            }

            $options = $withDeletesOption
                ? ['yes, with deletes', 'no']
                : ['yes', 'yes, with deletes', 'no'];

            if ($asJson) {
                $answer = $options[0];
            } else {
                $answer = $this->io->choice('Execute the listed database updates?', $options, $options[0]);
            }

            if ('no' === $answer) {
                return false;
            }

            if (!$asJson) {
                $this->io->section('Execute database migrations');
            }

            $count = 0;

            // If deletes should not be processed, recompile the commands without DROP statements
            if ('yes, with deletes' !== $answer) {
                $commands = $this->runner->compileSchemaCommands(true);
            }

            do {
                $commandExecuted = false;
                $exceptions = [];

                foreach ($commands as $key => $command) {
                    if ($asJson) {
                        $this->writeNdjson('schema-execute', ['command' => $command]);
                    } else {
                        $this->io->write(' * '.$command);
                    }

                    try {
                        $this->runner->executeSqlCommand($command);

                        ++$count;
                        $commandExecuted = true;

                        unset($commands[$key]);

                        if ($asJson) {
                            $this->writeNdjson('schema-result', [
                                'command' => $command,
                                'isSuccessful' => true,
                            ]);
                        } else {
                            $this->io->writeln('');
                        }
                    } catch (\Throwable $e) {
                        $exceptions[] = $e;

                        if ($asJson) {
                            $this->writeNdjson('schema-result', [
                                'command' => $command,
                                'isSuccessful' => false,
                                'message' => $e->getMessage(),
                            ]);
                        } else {
                            $this->io->writeln('......FAILED');
                        }
                    }
                }
            } while ($commandExecuted);

            if (!$asJson) {
                $this->io->success('Executed '.$count.' SQL queries.');

                foreach ($exceptions as $exception) {
                    $this->io->error($exception->getMessage());
                }
            }

            if ($exceptions) {
                return false;
            }

            // Do not run the update recursive if a hash was specified
            if (null !== $specifiedHash) {
                break;
            }
        }

        return true;
    }

    private function writeNdjson(string $type, array $data): void
    {
        // Make sure $type is the first in array but always wins
        $this->io->writeln(json_encode(['type' => $type] + $data, JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR));
    }

    private function validateDatabaseVersion(bool $asJson): bool
    {
        $message = $this->checks->validateDatabaseVersion();

        if (null === $message) {
            return true;
        }

        if ($asJson) {
            $this->writeNdjson('problem', ['message' => $message]);
        } else {
            $this->io->error($message);
        }

        return false;
    }
}
