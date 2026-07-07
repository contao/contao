<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration;

use Contao\CoreBundle\Doctrine\Backup\BackupManager;
use Contao\CoreBundle\Doctrine\Schema\MysqlInnodbRowSizeCalculator;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Connection\StaticServerVersionProvider;
use Doctrine\DBAL\Driver\Mysqli\Driver as MysqliDriver;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Schema\Table;

class DatabaseMigrationRunner
{
    public function __construct(
        private readonly CommandCompiler $commandCompiler,
        private readonly Connection $connection,
        private readonly MigrationCollection $migrations,
        private readonly BackupManager $backupManager,
        private readonly MysqlInnodbRowSizeCalculator $rowSizeCalculator,
    ) {
    }

    public function run(MigrationConfiguration $configuration, DatabaseMigrationObserverInterface|null $observer = null): DatabaseMigrationResult
    {
        if (
            !$observer && (
                WarningMode::Ask === $configuration->getWarningMode()
                || MigrationExecutionMode::Ask === $configuration->getMigrationExecutionMode()
                || SchemaUpdateMode::Ask === $configuration->getSchemaUpdateMode()
            )
        ) {
            return DatabaseMigrationResult::failure('An observer is required when ask mode is enabled.');
        }

        if ($errors = $this->compileConfigurationErrors()) {
            foreach ($errors as $error) {
                $this->notify($observer, new DatabaseMigrationEvent(DatabaseMigrationEventType::Problem, [
                    'message' => $error,
                    'severity' => 'warning',
                ]));
            }
            $this->notify($observer, new DatabaseMigrationEvent(DatabaseMigrationEventType::ConfigurationSummary));

            return DatabaseMigrationResult::failure(implode("\n\n", $errors));
        }

        $migrationsAllowed = !$configuration->isSchemaOnly();
        $schemaAllowed = !$configuration->isMigrationsOnly();
        $schemaSkipDropStatements = $this->shouldSkipDropStatementsForBackup($configuration);

        if (!$configuration->isDryRun() && $configuration->shouldCreateBackup()) {
            if (!$this->createBackup($observer, $schemaSkipDropStatements)) {
                return DatabaseMigrationResult::failure();
            }
        }

        if (!$this->validateDatabaseVersion($observer)) {
            return DatabaseMigrationResult::failure('Wrong database version configured.');
        }

        $migrationsExecuted = false;

        if ($migrationsAllowed && MigrationExecutionMode::Skip !== $configuration->getMigrationExecutionMode()) {
            if (!$this->executeMigrations($configuration, $observer, $migrationsExecuted)) {
                return DatabaseMigrationResult::failure();
            }
        }

        if ($schemaAllowed && SchemaUpdateMode::Skip !== $configuration->getSchemaUpdateMode()) {
            $schemaDryRun = $configuration->isDryRun() || (null !== $configuration->getHash() && $migrationsExecuted);

            if (!$this->executeSchemaDiff($configuration, $observer, $schemaDryRun)) {
                return DatabaseMigrationResult::failure();
            }
        }

        if (
            !$configuration->isDryRun()
            && !$configuration->isMigrationsOnly()
            && !$configuration->isSchemaOnly()
            && null === $configuration->getHash()
            && MigrationExecutionMode::Skip !== $configuration->getMigrationExecutionMode()
        ) {
            if (!$this->executeMigrations($configuration, $observer)) {
                return DatabaseMigrationResult::failure();
            }
        }

        return DatabaseMigrationResult::success();
    }

    private function createBackup(DatabaseMigrationObserverInterface|null $observer, bool $skipDropStatements): bool
    {
        if (!$this->hasWorkToDo($skipDropStatements)) {
            $this->notify($observer, new DatabaseMigrationEvent(
                DatabaseMigrationEventType::BackupResult,
                ['skipped' => true, 'message' => 'Database dump skipped because there are no migrations to execute.'],
            ));

            return true;
        }

        $config = $this->backupManager->createCreateConfig();
        $this->notify($observer, new DatabaseMigrationEvent(
            DatabaseMigrationEventType::BackupResult,
            ['started' => true, 'name' => $config->getBackup()->getFilename()],
        ));

        try {
            $this->backupManager->create($config);
        } catch (\Throwable $exception) {
            $this->notify($observer, new DatabaseMigrationEvent(DatabaseMigrationEventType::Error, [
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ]));

            return false;
        }

        $this->notify($observer, new DatabaseMigrationEvent(DatabaseMigrationEventType::BackupResult, $config->getBackup()->toArray()));

        return true;
    }

    private function executeMigrations(MigrationConfiguration $configuration, DatabaseMigrationObserverInterface|null $observer, bool &$executed = false): bool
    {
        $loopControl = 19;
        $executed = false;

        while (true) {
            $migrationLabels = [...$this->migrations->getPendingNames()];
            $actualHash = hash('sha256', json_encode($migrationLabels, JSON_THROW_ON_ERROR));

            if (MigrationExecutionMode::Ask === $configuration->getMigrationExecutionMode()) {
                $decision = $this->notify($observer, new DatabaseMigrationEvent(
                    DatabaseMigrationEventType::MigrationPending,
                    [
                        'names' => $migrationLabels,
                        'hash' => $actualHash,
                    ],
                    DatabaseMigrationDecision::Execute,
                ));

                if (DatabaseMigrationDecision::Skip === $decision) {
                    return false;
                }
            } else {
                $this->notify($observer, new DatabaseMigrationEvent(DatabaseMigrationEventType::MigrationPending, [
                    'names' => $migrationLabels,
                    'hash' => $actualHash,
                ]));
            }

            if ([] === $migrationLabels || $configuration->isDryRun()) {
                break;
            }

            if (null !== ($hash = $configuration->getHash()) && $hash !== $actualHash) {
                throw new DatabaseMigrationHashMismatchException(\sprintf('Specified hash "%s" does not match the actual hash "%s"', $hash, $actualHash));
            }

            $executed = true;
            $this->notify($observer, new DatabaseMigrationEvent(DatabaseMigrationEventType::MigrationExecuteStart));
            $batchCount = 0;
            $unexpectedPendingMigrationMessage = null;

            try {
                foreach ($this->migrations->run($migrationLabels) as $result) {
                    ++$batchCount;

                    $this->notify($observer, new DatabaseMigrationEvent(DatabaseMigrationEventType::MigrationResult, [
                        'message' => $result->getMessage(),
                        'isSuccessful' => $result->isSuccessful(),
                    ]));
                }
            } catch (UnexpectedPendingMigrationException $exception) {
                $unexpectedPendingMigrationMessage = $exception->getMessage();
                $this->notify($observer, new DatabaseMigrationEvent(DatabaseMigrationEventType::MigrationResult, [
                    'message' => $exception->getMessage(),
                    'isSuccessful' => false,
                    'unexpectedPending' => true,
                ]));
            }

            $this->notify($observer, new DatabaseMigrationEvent(DatabaseMigrationEventType::MigrationSummary, [
                'count' => $batchCount,
                'exception' => $unexpectedPendingMigrationMessage,
                'restart' => null !== $unexpectedPendingMigrationMessage,
            ]));

            if (null !== $unexpectedPendingMigrationMessage) {
                continue;
            }

            if (null !== $configuration->getHash()) {
                break;
            }

            if ($loopControl-- < 1) {
                $this->notify($observer, new DatabaseMigrationEvent(DatabaseMigrationEventType::Error, [
                    'message' => 'The migrations were stopped after 19 iterations as a precaution. There is a high chance of an infinite loop of migrations.',
                ]));

                return false;
            }

            if (0 === $batchCount) {
                break;
            }
        }

        return true;
    }

    private function executeSchemaDiff(MigrationConfiguration $configuration, DatabaseMigrationObserverInterface|null $observer, bool $dryRun = false): bool
    {
        $warnings = [...$this->compileConfigurationWarnings(), ...$this->compileSchemaWarnings($configuration->shouldSkipDropStatementsForSchemaWarnings())];

        if ($warnings) {
            $summaryDecision = $this->notify($observer, new DatabaseMigrationEvent(DatabaseMigrationEventType::WarningSummary, [
                'warnings' => $warnings,
                'prompt' => WarningMode::Ask === $configuration->getWarningMode(),
            ]));
            $warningDecision = null;

            foreach ($warnings as $warning) {
                $warningDecision = $this->notify($observer, new DatabaseMigrationEvent(
                    DatabaseMigrationEventType::Warning,
                    ['message' => $warning],
                )) ?? $warningDecision;
            }

            if (WarningMode::Abort === $configuration->getWarningMode()) {
                return false;
            }

            if (WarningMode::Ask === $configuration->getWarningMode() && DatabaseMigrationDecision::Abort === $summaryDecision) {
                return false;
            }

            if (!$summaryDecision && DatabaseMigrationDecision::Abort === $warningDecision) {
                return false;
            }
        }

        $lastCommands = [];

        while (true) {
            $commands = $this->commandCompiler->compileCommands();
            $hasNewCommands = [] !== array_diff($commands, $lastCommands);
            $lastCommands = $commands;

            $sortedCommands = $commands;
            sort($sortedCommands);
            $commandsHash = hash('sha256', json_encode($sortedCommands, JSON_THROW_ON_ERROR));

            $decision = null;

            if (SchemaUpdateMode::Ask === $configuration->getSchemaUpdateMode()) {
                $decision = $this->notify($observer, new DatabaseMigrationEvent(
                    DatabaseMigrationEventType::SchemaPending,
                    [
                        'commands' => $commands,
                        'hash' => $commandsHash,
                    ],
                    DatabaseMigrationDecision::WithoutDeletes,
                ));

                if (DatabaseMigrationDecision::Skip === $decision) {
                    return false;
                }

                if (!$decision) {
                    $decision = DatabaseMigrationDecision::WithoutDeletes;
                }
            } else {
                $this->notify($observer, new DatabaseMigrationEvent(DatabaseMigrationEventType::SchemaPending, [
                    'commands' => $commands,
                    'hash' => $commandsHash,
                ]));
                $decision = SchemaUpdateMode::WithDeletes === $configuration->getSchemaUpdateMode()
                    ? DatabaseMigrationDecision::WithDeletes
                    : DatabaseMigrationDecision::WithoutDeletes;
            }

            if (!$hasNewCommands) {
                return true;
            }

            if ($dryRun) {
                return true;
            }

            if (null !== ($hash = $configuration->getHash()) && $hash !== $commandsHash) {
                throw new DatabaseMigrationHashMismatchException(\sprintf('Specified hash "%s" does not match the actual hash "%s"', $hash, $commandsHash));
            }

            $exceptions = [];
            $executedCount = 0;

            $this->notify($observer, new DatabaseMigrationEvent(DatabaseMigrationEventType::SchemaExecuteStart));

            if (DatabaseMigrationDecision::WithoutDeletes === $decision) {
                $commands = $this->commandCompiler->compileCommands(true);
            }

            do {
                $commandExecuted = false;

                foreach ($commands as $key => $command) {
                    $this->notify($observer, new DatabaseMigrationEvent(DatabaseMigrationEventType::SchemaExecute, ['command' => $command]));

                    try {
                        try {
                            $this->connection->executeQuery($command);
                        } catch (\Throwable $exception) {
                            $this->fixFailedSqlCommand($command, $exception);
                        }

                        unset($commands[$key]);
                        $commandExecuted = true;
                        ++$executedCount;

                        $this->notify($observer, new DatabaseMigrationEvent(DatabaseMigrationEventType::SchemaResult, [
                            'command' => $command,
                            'isSuccessful' => true,
                        ]));
                    } catch (\Throwable $exception) {
                        $exceptions[] = $exception;

                        $this->notify($observer, new DatabaseMigrationEvent(DatabaseMigrationEventType::SchemaResult, [
                            'command' => $command,
                            'isSuccessful' => false,
                            'message' => $exception->getMessage(),
                        ]));
                    }
                }
            } while ($commandExecuted);

            $this->notify($observer, new DatabaseMigrationEvent(DatabaseMigrationEventType::SchemaSummary, [
                'count' => $executedCount,
                'exceptions' => array_map(static fn (\Throwable $exception): string => $exception->getMessage(), $exceptions),
            ]));

            if ($exceptions) {
                return false;
            }

            if (null !== $configuration->getHash()) {
                break;
            }
        }

        return true;
    }

    private function fixFailedSqlCommand(string $command, \Throwable $exception): void
    {
        if (
            !$exception instanceof DriverException
            || 1118 !== $exception->getCode()
            || !str_contains($exception->getMessage(), 'Row size too large')
            || !str_starts_with($command, 'ALTER TABLE ')
            || 1 !== (int) $this->connection->fetchOne('SELECT @@innodb_strict_mode')
        ) {
            throw $exception;
        }

        $this->connection->executeQuery('SET SESSION innodb_strict_mode = 0');

        try {
            $this->connection->executeQuery($command);

            $table = explode(' ', substr($command, 12), 2)[0];

            $this->connection->executeQuery("OPTIMIZE TABLE $table");
        } finally {
            $this->connection->executeQuery('SET SESSION innodb_strict_mode = 1');
        }
    }

    /**
     * @return array<int, string>
     */
    private function compileConfigurationErrors(): array
    {
        $errors = [];
        [$version] = explode('-', (string) $this->connection->fetchOne('SELECT @@version'));

        if (version_compare($version, '5.1.0', '<')) {
            $errors[] = <<<EOF
                Your database version is not supported!
                Contao requires at least MySQL 5.1.0 but you have version $version. Please update your database version.
                EOF;

            return $errors;
        }

        $options = $this->connection->getParams()['defaultTableOptions'] ?? [];

        if (null !== $collate = $options['collate'] ?? null) {
            $row = $this->connection->fetchAssociative("SHOW COLLATION LIKE '$collate'");

            if (false === $row) {
                $errors[] = <<<EOF
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

        if (null !== $engine = $options['engine'] ?? null) {
            $engineFound = false;
            $rows = $this->connection->fetchAllAssociative('SHOW ENGINES');

            foreach ($rows as $row) {
                if ($engine === $row['Engine']) {
                    $engineFound = true;
                    break;
                }
            }

            if (!$engineFound) {
                $errors[] = <<<EOF
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

        if ($engine && $collate && str_starts_with($collate, 'utf8mb4')) {
            if ('innodb' !== strtolower($engine)) {
                $errors[] = <<<EOF
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

            if ('' === $largePrefixSetting) {
                return $errors;
            }

            $vok = version_compare($version, '10', '>=') ? '10.2.2' : '5.7.7';

            if (version_compare($version, $vok, '>=')) {
                return $errors;
            }

            if (!\in_array(strtolower((string) $largePrefixSetting), ['1', 'on'], true)) {
                $errors[] = <<<'EOF'
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
                ($fileFormatSetting && 'barracuda' !== strtolower((string) $fileFormatSetting))
                || (null !== $filePerTableSetting && !\in_array(strtolower((string) $filePerTableSetting), ['1', 'on'], true))
            ) {
                $errors[] = <<<'EOF'
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
     * @return array<int, string>
     */
    private function compileConfigurationWarnings(): array
    {
        $warnings = [];
        $sqlMode = $this->connection->fetchOne('SELECT @@sql_mode');

        if (!array_intersect(explode(',', strtoupper((string) $sqlMode)), ['TRADITIONAL', 'STRICT_ALL_TABLES', 'STRICT_TRANS_TABLES'])) {
            $initOptionsKey = $this->connection->getDriver() instanceof MysqliDriver ? 3 : 1002;

            $warnings[] = <<<EOF
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
     * @return array<int, string>
     */
    private function compileSchemaWarnings(bool $skipDropStatements): array
    {
        $warnings = [];
        $schema = $this->commandCompiler->compileTargetSchema($skipDropStatements);

        foreach ($schema->getTables() as $table) {
            $warnings = [...$warnings, ...$this->compileTableWarnings($table)];
        }

        return $warnings;
    }

    /**
     * @return array<int, string>
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
            $warnings[] = "The row size of table {$table->getName()} is too large, try changing or deleting some columns:\n - MySQL row size: $mysqlSize of $mysqlLimit bytes\n - InnoDB row size: $innodbSize of $innodbLimit bytes";
        }

        return $warnings;
    }

    private function validateDatabaseVersion(DatabaseMigrationObserverInterface|null $observer): bool
    {
        $version = $this->connection->getServerVersion();
        $correctPlatform = $this->connection->getDriver()->getDatabasePlatform(new StaticServerVersionProvider($version));
        $currentPlatform = $this->connection->getDatabasePlatform();

        if ($correctPlatform::class === $currentPlatform::class) {
            return true;
        }

        $currentVersion = $this->connection->getParams()['serverVersion'] ?? '';

        if (!$currentVersion || !$version) {
            return true;
        }

        $message = <<<EOF
            Wrong database version configured!
            You have version $version but the database connection is configured to $currentVersion.
            EOF;

        $this->notify($observer, new DatabaseMigrationEvent(DatabaseMigrationEventType::Problem, [
            'message' => $message,
            'severity' => 'error',
        ]));

        return false;
    }

    private function hasWorkToDo(bool $skipDropStatements): bool
    {
        if ($this->migrations->hasPending()) {
            return true;
        }

        return [] !== $this->commandCompiler->compileCommands($skipDropStatements);
    }

    private function shouldSkipDropStatementsForBackup(MigrationConfiguration $configuration): bool
    {
        return $configuration->shouldSkipDropStatementsForBackup();
    }

    private function notify(DatabaseMigrationObserverInterface|null $observer, DatabaseMigrationEvent $event): DatabaseMigrationDecision|null
    {
        if (!$observer) {
            return null;
        }

        return $observer->notify($event);
    }
}
