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

use Contao\CoreBundle\Doctrine\Backup\BackupManager;
use Contao\CoreBundle\Doctrine\Schema\MysqlInnodbRowSizeCalculator;
use Contao\CoreBundle\Doctrine\Schema\SchemaProvider;
use Contao\CoreBundle\Migration\CommandCompiler;
use Contao\CoreBundle\Migration\MigrationCollection;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Mysqli\Driver as MysqliDriver;
use Doctrine\DBAL\Driver\ServerInfoAwareConnection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\VersionAwarePlatformDriver;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'contao:migrate',
    description: 'Executes migrations and updates the database schema.'
)]
class MigrateCommand extends Command
{
    private SymfonyStyle|null $io = null;

    public function __construct(
        private CommandCompiler $commandCompiler,
        private Connection $connection,
        private MigrationCollection $migrations,
        private BackupManager $backupManager,
        private SchemaProvider $schemaProvider,
        private MysqlInnodbRowSizeCalculator $rowSizeCalculator,
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

        try {
            if ($errors = $this->compileConfigurationErrors()) {
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

        // Return early if there is no work to be done
        if (!$this->hasWorkToDo()) {
            if (!$asJson) {
                $this->io->info('Database dump skipped because there are no migrations to execute.');
            }

            return true;
        }

        $config = $this->backupManager->createCreateConfig();

        if (!$asJson) {
            $this->io->info(sprintf(
                'Creating a database dump to "%s" with the default options. Use --no-backup to disable this feature.',
                $config->getBackup()->getFilename()
            ));
        }

        try {
            $this->backupManager->create($config);

            if ($asJson) {
                $this->writeNdjson('backup-result', $config->getBackup()->toArray());
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
            throw new InvalidOptionException(sprintf('Unsupported format "%s".', $input->getOption('format')));
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

    private function hasWorkToDo(): bool
    {
        // There are some pending migrations
        if ($this->migrations->hasPending()) {
            return true;
        }

        if (\count($this->commandCompiler->compileCommands()) > 0) {
            return true;
        }

        return false;
    }

    private function executeMigrations(bool &$dryRun, bool $asJson, string $specifiedHash = null): bool
    {
        while (true) {
            $first = true;
            $migrationLabels = [];

            foreach ($this->migrations->getPendingNames() as $migration) {
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

            $actualHash = hash('sha256', json_encode($migrationLabels));

            if ($asJson) {
                $this->writeNdjson('migration-pending', ['names' => $migrationLabels, 'hash' => $actualHash]);
            }

            if ($first || $dryRun) {
                break;
            }

            if (null !== $specifiedHash && $specifiedHash !== $actualHash) {
                throw new InvalidOptionException(sprintf('Specified hash "%s" does not match the actual hash "%s"', $specifiedHash, $actualHash));
            }

            if (!$asJson) {
                if (!$this->io->confirm('Execute the listed migrations?')) {
                    return false;
                }

                $this->io->section('Execute migrations');
            }

            $count = 0;

            /** @var MigrationResult $result */
            foreach ($this->migrations->run() as $result) {
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
                $this->io->success('Executed '.$count.' migrations.');
            }

            if (null !== $specifiedHash) {
                // Do not run the schema update after migrations got executed
                // if a hash was specified, because that hash could never match
                // both, migrations and schema updates
                $dryRun = true;

                // Do not run the update recursive if a hash was specified
                break;
            }
        }

        return true;
    }

    private function executeSchemaDiff(bool $dryRun, bool $asJson, bool $withDeletesOption, string $specifiedHash = null): bool
    {
        if ($warnings = [...$this->compileConfigurationWarnings(), ...$this->compileSchemaWarnings()]) {
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
            $commands = $this->commandCompiler->compileCommands();

            $hasNewCommands = \count(array_diff($commands, $lastCommands)) > 0;
            $lastCommands = $commands;

            $commandsHash = hash('sha256', json_encode($commands));

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
                throw new InvalidOptionException(sprintf('Specified hash "%s" does not match the actual hash "%s"', $specifiedHash, $commandsHash));
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
                $commands = $this->commandCompiler->compileCommands(true);
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
                        $this->connection->executeQuery($command);

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

                if (\count($exceptions)) {
                    foreach ($exceptions as $exception) {
                        $this->io->error($exception->getMessage());
                    }
                }
            }

            if (\count($exceptions)) {
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
        $this->io->writeln(
            json_encode(
                // make sure $type is the first in array but always wins
                ['type' => $type] + $data,
                JSON_INVALID_UTF8_SUBSTITUTE
            )
        );

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \JsonException(json_last_error_msg());
        }
    }

    /**
     * @return array<int,string>
     */
    private function compileConfigurationErrors(): array
    {
        $errors = [];

        // Check if the database version is too old
        [$version] = explode('-', (string) $this->connection->fetchOne('SELECT @@version'));

        if (version_compare($version, '5.1.0', '<')) {
            $errors[] =
                <<<EOF
                    Your database version is not supported!
                    Contao requires at least MySQL 5.1.0 but you have version $version. Please update your database version.
                    EOF;

            return $errors;
        }

        $options = $this->connection->getParams()['defaultTableOptions'] ?? [];

        // Check the collation if the user has configured it
        if (null !== $collate = ($options['collate'] ?? null)) {
            $row = $this->connection->fetchAssociative("SHOW COLLATION LIKE '$collate'");

            if (false === $row) {
                $errors[] =
                    <<<EOF
                        The configured collation is not supported!
                        The configured collation "$collate" is not available on your server. Please install it (recommended) or configure a different character set and collation in the "config/config.yaml" file.

                        dbal:
                            connections:
                                default:
                                    default_table_options:
                                        charset: utf8
                                        collation: utf8_unicode_ci
                        EOF;
            }
        }

        // Check the engine if the user has configured it
        if (null !== $engine = ($options['engine'] ?? null)) {
            $engineFound = false;
            $rows = $this->connection->fetchAllAssociative('SHOW ENGINES');

            foreach ($rows as $row) {
                if ($engine === $row['Engine']) {
                    $engineFound = true;
                    break;
                }
            }

            if (!$engineFound) {
                $errors[] =
                    <<<EOF
                        The configured database engine is not supported!
                        The configured database engine "$engine" is not available on your server. Please install it (recommended) or configure a different database engine in the "config/config.yaml" file.

                        dbal:
                            connections:
                                default:
                                    default_table_options:
                                        engine: MyISAM
                                        row_format: ~
                        EOF;
            }
        }

        // Check if utf8mb4 can be used if the user has configured it
        if ($engine && $collate && str_starts_with($collate, 'utf8mb4')) {
            if ('innodb' !== strtolower($engine)) {
                $errors[] =
                    <<<EOF
                        Invalid combination of database engine and collation!
                        The configured database engine "$engine" does not support utf8mb4. Please use InnoDB instead (recommended) or configure a different character set and collation in the "config/config.yaml" file.

                        dbal:
                            connections:
                                default:
                                    default_table_options:
                                        charset: utf8
                                        collation: utf8_unicode_ci
                        EOF;

                return $errors;
            }

            $largePrefixSetting = $this->connection->fetchAssociative("SHOW VARIABLES LIKE 'innodb_large_prefix'")['Value'] ?? '';

            // The variable no longer exists as of MySQL 8 and MariaDB 10.3
            if ('' === $largePrefixSetting) {
                return $errors;
            }

            // As there is no reliable way to get the vendor (see #84), we are
            // guessing based on the version number. The check will not be run
            // as of MySQL 8 and MariaDB 10.3, so this should be safe.
            $vok = version_compare($version, '10', '>=') ? '10.2.2' : '5.7.7';

            // Large prefixes are always enabled as of MySQL 5.7.7 and MariaDB 10.2.2
            if (version_compare($version, $vok, '>=')) {
                return $errors;
            }

            // The innodb_large_prefix option is disabled
            if (!\in_array(strtolower((string) $largePrefixSetting), ['1', 'on'], true)) {
                $errors[] =
                    <<<'EOF'
                        The "innodb_large_prefix" option is not enabled!
                        The "innodb_large_prefix" option is not enabled on your server. Please enable it (recommended) or configure a different character set and collation in the "config/config.yaml" file.

                        dbal:
                            connections:
                                default:
                                    default_table_options:
                                        charset: utf8
                                        collation: utf8_unicode_ci
                        EOF;
            }

            $fileFormatSetting = $this->connection->fetchAssociative("SHOW VARIABLES LIKE 'innodb_file_format'")['Value'] ?? '';
            $filePerTableSetting = $this->connection->fetchAssociative("SHOW VARIABLES LIKE 'innodb_file_per_table'")['Value'] ?? null;

            if (
                // The InnoDB file format is not Barracuda
                ($fileFormatSetting && 'barracuda' !== strtolower((string) $fileFormatSetting)) ||
                // The innodb_file_per_table option is disabled
                (null !== $filePerTableSetting && !\in_array(strtolower((string) $filePerTableSetting), ['1', 'on'], true))
            ) {
                $errors[] =
                    <<<'EOF'
                        InnoDB is not configured properly!
                        Using large prefixes in MySQL versions prior to 5.7.7 and MariaDB versions prior to 10.2 requires the "Barracuda" file format and the "innodb_file_per_table" option.

                            innodb_large_prefix = 1
                            innodb_file_format = Barracuda
                            innodb_file_per_table = 1
                        EOF;
            }
        }

        return $errors;
    }

    /**
     * @return array<int,string>
     */
    private function compileConfigurationWarnings(): array
    {
        $warnings = [];

        // Suggest running in strict mode
        $sqlMode = $this->connection->fetchOne('SELECT @@sql_mode');

        if (!array_intersect(explode(',', strtoupper($sqlMode)), ['TRADITIONAL', 'STRICT_ALL_TABLES', 'STRICT_TRANS_TABLES'])) {
            $initOptionsKey = $this->connection->getDriver() instanceof MysqliDriver ? 3 : 1002;

            $warnings[] =
                <<<EOF
                    Running MySQL in non-strict mode can cause corrupt or truncated data.
                    Please enable the strict mode either in your "my.cnf" file or configure the connection options in the "config/config.yaml" as follows:

                    dbal:
                        connections:
                            default:
                                options:
                                    $initOptionsKey: "SET SESSION sql_mode=(SELECT CONCAT(@@sql_mode, ',TRADITIONAL'))"
                    EOF;
        }

        return $warnings;
    }

    /**
     * @return array<int,string>
     */
    private function compileSchemaWarnings(): array
    {
        $warnings = [];
        $schema = $this->schemaProvider->createSchema();

        foreach ($schema->getTables() as $table) {
            $warnings = [...$warnings, ...$this->compileTableWarnings($table)];
        }

        return $warnings;
    }

    /**
     * @return array<int,string>
     */
    private function compileTableWarnings(Table $table): array
    {
        $warnings = [];

        if ($table->hasOption('engine') && 'innodb' !== strtolower($table->getOption('engine'))) {
            return $warnings;
        }

        $mysqlSize = $this->rowSizeCalculator->getMysqlRowSize($table);
        $mysqlLimit = $this->rowSizeCalculator->getMysqlRowSizeLimit();
        $innodbSize = $this->rowSizeCalculator->getInnodbRowSize($table);
        $innodbLimit = $this->rowSizeCalculator->getInnodbRowSizeLimit();

        if ($mysqlSize > $mysqlLimit || $innodbSize > $innodbLimit) {
            $warnings[] = "The row size of table {$table->getName()} is too large:\n - MySQL row size: $mysqlSize of $mysqlLimit bytes\n - InnoDB row size: $innodbSize of $innodbLimit bytes";
        }

        return $warnings;
    }

    private function validateDatabaseVersion(bool $asJson): bool
    {
        // TODO: Find a replacement for getWrappedConnection() once doctrine/dbal 4.0 is released
        $driverConnection = $this->connection->getWrappedConnection();

        if (!$driverConnection instanceof ServerInfoAwareConnection) {
            return true;
        }

        $driver = $this->connection->getDriver();

        if (!$driver instanceof VersionAwarePlatformDriver) {
            return true;
        }

        $version = $driverConnection->getServerVersion();
        $correctPlatform = $driver->createDatabasePlatformForVersion($version);

        /** @var AbstractPlatform $currentPlatform */
        $currentPlatform = $this->connection->getDatabasePlatform();

        if ($correctPlatform::class === $currentPlatform::class) {
            return true;
        }

        // If serverVersion is not configured, we will actually never end up here
        $currentVersion = $this->connection->getParams()['serverVersion'] ?? '';

        $message =
            <<<EOF
                Wrong database version configured!
                You have version $version but the database connection is configured to $currentVersion.
                EOF;

        if ($asJson) {
            $this->writeNdjson('problem', ['message' => $message]);
        } else {
            $this->io->error($message);
        }

        return false;
    }
}
