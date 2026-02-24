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

use Contao\Controller;
use Contao\CoreBundle\Doctrine\Backup\Config\CreateConfig;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\Keywords\KeywordList;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;

class MigrationDumper implements DumperInterface
{
    private array $quoteCache = [];

    public function __construct(private readonly ContaoFramework $framework)
    {
    }

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

    private function doDump(Connection $connection, CreateConfig $config): \Generator
    {
        $this->framework->initialize();

        yield 'SET FOREIGN_KEY_CHECKS = 0;';

        $this->disableQueryBuffering($connection);

        $schemaManager = $connection->createSchemaManager();
        $platform = clone $connection->getDatabasePlatform();

        $reflection = (new \ReflectionClass($platform))->getProperty('_keywords');
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
     */
    private function dumpViews(AbstractSchemaManager $schemaManager, AbstractPlatform $platform): \Generator
    {
        foreach ($schemaManager->listViews() as $view) {
            yield \sprintf('-- BEGIN VIEW %s', $view->getName());
            yield \sprintf('CREATE OR REPLACE VIEW %s AS %s;', $view->getQuotedName($platform), $view->getSql());
        }
    }

    private function dumpSchema(AbstractPlatform $platform, Table $table): \Generator
    {
        yield \sprintf('-- BEGIN STRUCTURE %s', $table->getName());
        yield \sprintf('DROP TABLE IF EXISTS `%s`;', $table->getName());

        foreach ($platform->getCreateTableSQL($table) as $statement) {
            yield $statement.';';
        }
    }

    private function dumpData(Connection $connection, Table $table): \Generator
    {
        Controller::loadDataContainer($table->getName());

        $columnDcaConfigs = [];

        foreach ($GLOBALS['TL_DCA'][$table->getName()]['fields'] ?? [] as $fieldName => $fieldConfig) {
            $columnDcaConfigs[strtolower($fieldName)] = $fieldConfig;
        }

        yield \sprintf('-- BEGIN DATA %s', $table->getName());

        $values = [];
        $columnBindingTypes = [];
        $columnUtf8Charsets = [];

        foreach ($table->getColumns() as $column) {
            $columnName = $column->getName();
            $values[] = "`$columnName` AS `$columnName`";
            $columnBindingTypes[$columnName] = $column->getType()->getBindingType();
            $columnUtf8Charsets[$columnName] = \in_array(strtolower($column->getPlatformOptions()['charset'] ?? ''), ['utf8', 'utf8mb4'], true);
        }

        $values = implode(', ', $values);
        $tableName = $table->getName();
        $rows = $connection->executeQuery("SELECT $values FROM `$tableName`");

        /** @var array<string, float|int|string|null> $row[] */
        foreach ($rows->iterateAssociative() as $row) {
            $insertColumns = [];
            $insertValues = [];

            foreach ($row as $columnName => $value) {
                $insertColumns[] = "`$columnName`";

                $insertValues[] = $this->formatValueForDump(
                    $value,
                    $columnBindingTypes[$columnName],
                    $columnUtf8Charsets[$columnName],
                    $connection,
                    $columnDcaConfigs[$columnName] ?? null,
                );
            }

            $insertColumns = implode(', ', $insertColumns);
            $insertValues = implode(', ', $insertValues);

            yield "INSERT INTO `$tableName` ($insertColumns) VALUES ($insertValues);";
        }
    }

    private function formatValueForDump(float|int|string|null $value, ParameterType|int $columnBindingType, bool $isUtf8Charset, Connection $connection, array|null $dcaFieldConfig): string
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

        if ($dcaFieldConfig) {
            $value = $this->migrateToOutputEncoding($value, $dcaFieldConfig);
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

    private function migrateToOutputEncoding(string $value, array $dcaFieldConfig): string
    {
        if (
            ($dcaFieldConfig['eval']['useRawRequestData'] ?? null)
            || ($dcaFieldConfig['eval']['allowHtml'] ?? null)
            || ($dcaFieldConfig['eval']['preserveTags'] ?? null)
            || ($dcaFieldConfig['eval']['rte'] ?? null) === 'ace|html'
            || str_starts_with($dcaFieldConfig['eval']['rte'] ?? '', 'tiny')
            || ($dcaFieldConfig['inputType'] ?? null) === 'fileTree'
        ) {
            return $value;
        }

        $value = StringUtil::deserialize($value);

        if ($dcaFieldConfig['eval']['decodeEntities'] ?? null) {
            $replace = static function (&$value): void {
                if (\is_string($value)) {
                    $value = str_replace(['&#60;', '&#92;0'], ['<', '\0'], $value);
                }
            };
        } else {
            $replace = static function (&$value): void {
                if (\is_string($value)) {
                    // TODO: can we drop this?
                    $value = str_replace(['&#123;&#123;', '&#125;&#125;'], ['[{]', '[}]'], $value);
                    $value = html_entity_decode($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
                }
            };
        }

        if (\is_string($value)) {
            $replace($value);

            return $value;
        }

        array_walk_recursive($value, $replace);

        return serialize($value);
    }

    /**
     * @param AbstractSchemaManager<AbstractPlatform> $schemaManager
     *
     * @return array<Table>
     */
    private function getTablesToDump(AbstractSchemaManager $schemaManager, CreateConfig $config): array
    {
        $allTables = $schemaManager->listTables();
        $filteredTables = [];

        foreach ($allTables as $table) {
            if (\in_array($table->getName(), $config->getTablesToIgnore(), true)) {
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
