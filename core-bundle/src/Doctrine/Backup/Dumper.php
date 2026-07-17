<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Doctrine\Backup;

use Contao\CoreBundle\Doctrine\Backup\Config\CreateConfig;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\Keywords\KeywordList;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;

class Dumper implements DumperInterface
{
    private array $quoteCache = [];

    /**
     * @return \Generator<string>
     */
    public function dump(Connection $connection, CreateConfig $config): \Generator
    {
        try {
            yield from $this->doDump($connection, $config);
        } catch (\Exception $exception) {
            if ($exception instanceof BackupManagerException) {
                throw $exception;
            }

            throw new BackupManagerException($exception->getMessage(), 0, $exception);
        } finally {
            $this->quoteCache = [];
        }
    }

    /**
     * @return \Generator<string>
     */
    private function doDump(Connection $connection, CreateConfig $config): \Generator
    {
        yield 'SET FOREIGN_KEY_CHECKS = 0;';

        $this->disableQueryBuffering($connection);

        $schemaManager = $connection->createSchemaManager();
        $platform = clone $connection->getDatabasePlatform();

        $reflection = new \ReflectionClass($platform)->getProperty('_keywords');
        $reflection->setValue($platform, $this->getCompatibleKeywords());

        foreach ($this->getTablesToDump($schemaManager, $config) as $table) {
            yield from $this->dumpSchema($platform, $table);
            yield from $this->dumpData($connection, $table);
        }

        yield from $this->dumpViews($schemaManager, $platform);

        // Triggers are currently not supported (contributions welcome!)

        yield 'SET FOREIGN_KEY_CHECKS = 1;';
    }

    private function disableQueryBuffering(Connection $connection): void
    {
        $pdo = $connection->getNativeConnection();

        if (!$pdo instanceof \PDO) {
            return;
        }

        // Already disabled
        if (!$pdo->getAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY)) {
            return;
        }

        $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
    }

    /**
     * @param AbstractSchemaManager<AbstractPlatform> $schemaManager
     *
     * @return \Generator<string>
     */
    private function dumpViews(AbstractSchemaManager $schemaManager, AbstractPlatform $platform): \Generator
    {
        foreach ($schemaManager->introspectViews() as $view) {
            $viewName = $view->getObjectName();

            yield \sprintf('-- BEGIN VIEW %s', $viewName->getUnqualifiedName()->getValue());
            yield \sprintf('CREATE OR REPLACE VIEW %s AS %s;', $viewName->toSQL($platform), $view->getSql());
        }
    }

    /**
     * @return \Generator<string>
     */
    private function dumpSchema(AbstractPlatform $platform, Table $table): \Generator
    {
        $tableName = $table->getObjectName();

        yield \sprintf('-- BEGIN STRUCTURE %s', $tableName->getUnqualifiedName()->getValue());
        yield \sprintf('DROP TABLE IF EXISTS %s;', $tableName->toSQL($platform));

        foreach ($platform->getCreateTableSQL($table) as $statement) {
            yield $statement.';';
        }
    }

    /**
     * @return \Generator<string>
     */
    private function dumpData(Connection $connection, Table $table): \Generator
    {
        $tableName = $table->getObjectName();

        yield \sprintf('-- BEGIN DATA %s', $tableName->getUnqualifiedName()->getValue());

        $values = [];
        $columnBindingTypes = [];
        $columnUtf8Charsets = [];

        $platform = $connection->getDatabasePlatform();
        $tableNameSql = $tableName->toSQL($platform);

        foreach ($table->getColumns() as $column) {
            $columnName = $column->getObjectName();
            $values[] = $columnName->toSQL($platform);

            $key = $columnName->getIdentifier()->getValue();
            $columnBindingTypes[$key] = $column->getType()->getBindingType();
            $columnUtf8Charsets[$key] = \in_array(strtolower($column->getCharset() ?? ''), ['utf8', 'utf8mb4'], true);
        }

        $values = implode(', ', $values);
        $rows = $connection->executeQuery("SELECT $values FROM $tableNameSql");

        /** @var array<string, float|int|string|null> $row[] */
        foreach ($rows->iterateAssociative() as $row) {
            $insertColumns = [];
            $insertValues = [];

            foreach ($row as $columnName => $value) {
                $insertColumns[] = $platform->quoteSingleIdentifier($columnName);

                $insertValues[] = $this->formatValueForDump(
                    $value,
                    $columnBindingTypes[$columnName],
                    $columnUtf8Charsets[$columnName],
                    $connection,
                );
            }

            $insertColumns = implode(', ', $insertColumns);
            $insertValues = implode(', ', $insertValues);

            yield "INSERT INTO $tableNameSql ($insertColumns) VALUES ($insertValues);";
        }
    }

    private function formatValueForDump(float|int|string|null $value, ParameterType|int $columnBindingType, bool $isUtf8Charset, Connection $connection): string
    {
        if (null === $value) {
            return 'NULL';
        }

        $value = (string) $value;

        if ('' === $value) {
            return "''";
        }

        // In MySQL, booleans are stored as tinyint, so we don't need to quote that either
        if (ParameterType::INTEGER === $columnBindingType || ParameterType::BOOLEAN === $columnBindingType) {
            return $value;
        }

        // Non-ASCII values
        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F-\xFF]/', $value) && (!$isUtf8Charset || !preg_match('//u', $value))) {
            return '0x'.bin2hex($value);
        }

        if (isset($this->quoteCache[$value])) {
            return $this->quoteCache[$value];
        }

        // Prevent the in-memory cache from growing forever on big databases
        if (\count($this->quoteCache) >= 100000) {
            $this->quoteCache = [];
        }

        return $this->quoteCache[$value] = $connection->quote($value);
    }

    /**
     * @param AbstractSchemaManager<AbstractPlatform> $schemaManager
     *
     * @return array<Table>
     */
    private function getTablesToDump(AbstractSchemaManager $schemaManager, CreateConfig $config): array
    {
        $allTables = $schemaManager->introspectTables();
        $filteredTables = [];

        foreach ($allTables as $table) {
            $tableName = $table->getObjectName()->getUnqualifiedName()->getValue();

            if (\in_array($tableName, $config->getTablesToIgnore(), true)) {
                continue;
            }

            $filteredTables[] = $table;
        }

        sort($filteredTables);

        return $filteredTables;
    }

    private function getCompatibleKeywords(): KeywordList
    {
        return new class() extends KeywordList {
            public function isKeyword(mixed $word): bool
            {
                return true;
            }

            protected function getKeywords(): array
            {
                return [];
            }

            public function getName(): string
            {
                return 'AllWordsAreKeywords';
            }
        };
    }
}
