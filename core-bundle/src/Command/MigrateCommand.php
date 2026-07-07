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

use Contao\CoreBundle\Command\Migration\ConsoleDatabaseMigrationObserver;
use Contao\CoreBundle\Migration\DatabaseMigrationEvent;
use Contao\CoreBundle\Migration\DatabaseMigrationEventType;
use Contao\CoreBundle\Migration\DatabaseMigrationHashMismatchException;
use Contao\CoreBundle\Migration\DatabaseMigrationRunner;
use Contao\CoreBundle\Migration\MigrationConfiguration;
use Contao\CoreBundle\Migration\MigrationExecutionMode;
use Contao\CoreBundle\Migration\SchemaUpdateMode;
use Contao\CoreBundle\Migration\WarningMode;
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
    public function __construct(private readonly DatabaseMigrationRunner $runner)
    {
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
        $format = (string) $input->getOption('format');

        if (!\in_array($format, ['txt', 'ndjson'], true)) {
            throw new InvalidOptionException(\sprintf('Unsupported format "%s".', $format));
        }

        $dryRun = (bool) $input->getOption('dry-run');
        $interactive = $input->isInteractive();
        $asJson = 'ndjson' === $format;

        if ($asJson && !$dryRun && $interactive) {
            throw new InvalidOptionException('Use --no-interaction or --dry-run together with --format=ndjson');
        }

        if ($input->getOption('migrations-only') && $input->getOption('schema-only')) {
            throw new InvalidOptionException('--migrations-only cannot be combined with --schema-only');
        }

        if ($input->getOption('migrations-only') && $input->getOption('with-deletes')) {
            throw new InvalidOptionException('--migrations-only cannot be combined with --with-deletes');
        }

        $io = new SymfonyStyle($input, $output);
        $observer = new ConsoleDatabaseMigrationObserver(
            $io,
            $asJson,
            (bool) $input->getOption('with-deletes'),
        );
        $configuration = $this->createConfiguration($input, $dryRun, $interactive);

        try {
            $result = $this->runner->run($configuration, $observer);
        } catch (DatabaseMigrationHashMismatchException $exception) {
            if (!$asJson) {
                throw new InvalidOptionException($exception->getMessage(), 0, $exception);
            }

            $observer->notify(new DatabaseMigrationEvent(
                DatabaseMigrationEventType::Error,
                [
                    'message' => $exception->getMessage(),
                    'code' => $exception->getCode(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => $exception->getTraceAsString(),
                ],
            ));

            return Command::FAILURE;
        } catch (\Throwable $exception) {
            if (!$asJson) {
                throw $exception;
            }

            $observer->notify(new DatabaseMigrationEvent(
                DatabaseMigrationEventType::Error,
                [
                    'message' => $exception->getMessage(),
                    'code' => $exception->getCode(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => $exception->getTraceAsString(),
                ],
            ));

            return Command::FAILURE;
        }

        if ($result->isSuccessful()) {
            if (!$asJson && !$input->getOption('migrations-only') && !$input->getOption('schema-only')) {
                $io->success('All migrations completed.');
            }

            return Command::SUCCESS;
        }

        return Command::FAILURE;
    }

    private function createConfiguration(InputInterface $input, bool $dryRun, bool $interactive): MigrationConfiguration
    {
        $schemaOnly = (bool) $input->getOption('schema-only');
        $migrationsOnly = (bool) $input->getOption('migrations-only');
        $withDeletes = (bool) $input->getOption('with-deletes');

        $warningMode = $interactive && !$dryRun ? WarningMode::Ask : WarningMode::Continue;
        $migrationExecutionMode = $schemaOnly
            ? MigrationExecutionMode::Skip
            : ($interactive && !$dryRun ? MigrationExecutionMode::Ask : MigrationExecutionMode::Execute);
        $schemaUpdateMode = $migrationsOnly
            ? SchemaUpdateMode::Skip
            : ($interactive && !$dryRun ? SchemaUpdateMode::Ask : ($withDeletes ? SchemaUpdateMode::WithDeletes : SchemaUpdateMode::WithoutDeletes));

        return MigrationConfiguration::create()
            ->withDryRun($dryRun)
            ->withCreateBackup(!$dryRun && !$input->getOption('no-backup'))
            ->withSchemaOnly($schemaOnly)
            ->withMigrationsOnly($migrationsOnly)
            ->withBackupSkipDropStatements(!$interactive && !$withDeletes)
            ->withSchemaWarningSkipDropStatements(!$withDeletes)
            ->withWarningMode($warningMode)
            ->withMigrationExecutionMode($migrationExecutionMode)
            ->withSchemaUpdateMode($schemaUpdateMode)
            ->withHash($input->getOption('hash') ? (string) $input->getOption('hash') : null)
        ;
    }
}
