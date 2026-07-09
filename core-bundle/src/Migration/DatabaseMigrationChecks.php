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

use Contao\CoreBundle\Doctrine\Schema\MysqlInnodbRowSizeCalculator;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Mysqli\Driver as MysqliDriver;
use Doctrine\DBAL\Schema\Table;

class DatabaseMigrationChecks
{
    public function __construct(
        private readonly CommandCompiler $commandCompiler,
        private readonly MysqlInnodbRowSizeCalculator $rowSizeCalculator,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function compileConfigurationErrors(Connection $connection): array
    {
        $errors = [];
        [$version] = explode('-', (string) $connection->fetchOne('SELECT @@version'));

        if (version_compare($version, '5.1.0', '<')) {
            $errors[] = <<<EOF
                Your database version is not supported!
                Contao requires at least MySQL 5.1.0 but you have version $version. Please update your database version.
                EOF;

            return $errors;
        }

        $options = $connection->getParams()['defaultTableOptions'] ?? [];

        if (null !== $collate = $options['collate'] ?? null) {
            $row = $connection->fetchAssociative("SHOW COLLATION LIKE '$collate'");

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
            $rows = $connection->fetchAllAssociative('SHOW ENGINES');

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

            $largePrefixSetting = $connection->fetchAssociative("SHOW VARIABLES LIKE 'innodb_large_prefix'")['Value'] ?? '';

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

            $fileFormatSetting = $connection->fetchAssociative("SHOW VARIABLES LIKE 'innodb_file_format'")['Value'] ?? '';
            $filePerTableSetting = $connection->fetchAssociative("SHOW VARIABLES LIKE 'innodb_file_per_table'")['Value'] ?? null;

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
    public function compileConfigurationWarnings(Connection $connection): array
    {
        $warnings = [];
        $sqlMode = $connection->fetchOne('SELECT @@sql_mode');

        if (!array_intersect(explode(',', strtoupper((string) $sqlMode)), ['TRADITIONAL', 'STRICT_ALL_TABLES', 'STRICT_TRANS_TABLES'])) {
            $initOptionsKey = $connection->getDriver() instanceof MysqliDriver ? 3 : 1002;

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
    public function compileSchemaWarnings(Connection $connection, bool $skipDropStatements): array
    {
        $warnings = [];
        $schema = $this->commandCompiler->compileTargetSchema($skipDropStatements);

        foreach ($schema->getTables() as $table) {
            $warnings = [...$warnings, ...$this->compileTableWarnings($table)];
        }

        return $warnings;
    }

    public function validateDatabaseVersion(Connection $connection): string|null
    {
        $version = $connection->getServerVersion();
        $correctPlatform = $connection->getDriver()->getDatabasePlatform(new \Doctrine\DBAL\Connection\StaticServerVersionProvider($version));
        $currentPlatform = $connection->getDatabasePlatform();

        if ($correctPlatform::class === $currentPlatform::class) {
            return null;
        }

        $currentVersion = $connection->getParams()['serverVersion'] ?? '';

        if (!$currentVersion || !$version) {
            return null;
        }

        return <<<EOF
            Wrong database version configured!
            You have version $version but the database connection is configured to $currentVersion.
            EOF;
    }

    /**
     * @return array<int, string>
     */
    private function compileTableWarnings(Table $table): array
    {
        $warnings = [];

        if ($table->hasOption('engine') && 'innodb' !== strtolower((string) $table->getOption('engine'))) {
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
}
