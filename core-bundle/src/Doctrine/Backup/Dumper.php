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
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\BinaryType;

class Dumper implements DumperInterface
{
    /**
     * @var resource
     */
    private $fileHandle;

    /**
     * @var \DeflateContext|false|null
     */
    private $deflateContext;

    public function dump(Connection $connection, CreateConfig $config): void
    {
        try {
            $this->doDump($connection, $config);
        } catch (\Exception $exception) {
            if ($exception instanceof BackupManagerException) {
                throw $exception;
            }

            throw new BackupManagerException($exception->getMessage(), 0, $exception);
        }
    }

    public function doDump(Connection $connection, CreateConfig $config): void
    {
        $this->init($config);
        $this->writeln('SET FOREIGN_KEY_CHECKS = 0;');

        foreach ($this->getTablesToDump($connection, $config) as $table) {
            $this->dumpSchema($connection, $config, $table);
            $this->dumpData($connection, $config, $table);
        }

        // Views and triggers are currently not supported (contributions welcome!)

        $this->writeln('SET FOREIGN_KEY_CHECKS = 1;');
        $this->finish();
    }

    private function dumpSchema(Connection $connection, CreateConfig $config, Table $table): void
    {
        $this->writeln(sprintf('-- BEGIN STRUCTURE %s', $table->getName()));
        $this->writeln(sprintf('DROP TABLE IF EXISTS `%s`;', $table->getName()));

        foreach ($connection->getDatabasePlatform()->getCreateTableSQL($table) as $statement) {
            $this->writeln($statement.';');
        }
    }

    private function dumpData(Connection $connection, CreateConfig $config, Table $table): void
    {
        $this->writeln(sprintf('-- BEGIN DATA %s', $table->getName()));
        $values = [];

        foreach ($table->getColumns() as $column) {
            if ($column->getType() instanceof BinaryType) {
                $values[] = sprintf('HEX(`%s`) AS `%s`', $column->getName(), $column->getName());
            } else {
                $values[] = sprintf('`%s` AS `%s`', $column->getName(), $column->getName());
            }
        }

        $rows = $connection->executeQuery(sprintf('SELECT %s FROM `%s`', implode(', ', $values), $table->getName()));

        foreach ($rows->fetchAllAssociative() as $row) {
            $insertColumns = [];
            $insertValues = [];

            foreach ($row as $columnName => $value) {
                $insertColumns[] = sprintf('`%s`', $columnName);
                $insertValues[] = $this->formatValueForDump($value, $table->getColumn($columnName), $connection);
            }

            $this->writeln(sprintf(
                'INSERT INTO `%s` (%s) VALUES (%s);',
                $table->getName(),
                implode(', ', $insertColumns),
                implode(', ', $insertValues)
            ));
        }
    }

    private function formatValueForDump($value, Column $column, Connection $connection): string
    {
        if (null === $value) {
            return 'NULL';
        }

        if ($column->getType() instanceof BinaryType) {
            return sprintf('UNHEX(%s)', $connection->quote($value));
        }

        return $connection->quote($value);
    }

    /**
     * @throws Exception
     *
     * @return array<Table>
     */
    private function getTablesToDump(Connection $connection, CreateConfig $config): array
    {
        $allTables = $connection->createSchemaManager()->listTables();
        $filteredTables = [];

        foreach ($allTables as $table) {
            if (\in_array($table->getName(), $config->getTablesToIgnore(), true)) {
                continue;
            }

            $filteredTables[] = $table;
        }

        return $filteredTables;
    }

    private function init(CreateConfig $config): void
    {
        $this->fileHandle = fopen($config->getBackup()->getFilepath(), 'w');
        $this->deflateContext = $config->isGzCompressionEnabled() ? deflate_init(ZLIB_ENCODING_GZIP, ['level' => 9]) : null;

        // Header lines
        $this->writeln($config->getDumpHeader());
        $this->writeln('-- Generated at '.$config->getBackup()->getCreatedAt()->format(\DateTimeInterface::ISO8601));
        $this->writeln('SET NAMES utf8;');
    }

    private function finish(): void
    {
        if ($this->deflateContext) {
            fwrite($this->fileHandle, deflate_add($this->deflateContext, '', ZLIB_FINISH));
        }

        fclose($this->fileHandle);
    }

    private function writeln(string $line): void
    {
        $line .= \PHP_EOL;
        $this->write($line);
    }

    private function write(string $line): void
    {
        if ($this->deflateContext) {
            $line = deflate_add($this->deflateContext, $line, ZLIB_NO_FLUSH);
        }

        @fwrite($this->fileHandle, $line);
        fflush($this->fileHandle);
    }
}
