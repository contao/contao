<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Doctrine\Schema;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;

/**
 * @internal
 */
class MysqlInnodbRowSizeCalculator
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getMysqlRowSize(Table $table): int
    {
        $tableCharset = $table->hasOption('charset') ? $table->getOption('charset') : 'utf8mb4';
        $size = 0;

        foreach ($table->getColumns() as $column) {
            $charset = $tableCharset;

            if ($column->toArray()['charset'] ?? null) {
                $charset = $column->toArray()['charset'];
            } elseif ($column->toArray()['collation'] ?? null) {
                $charset = explode('_', $column->toArray()['collation'])[0];
            }

            $size += $this->getMysqlColumnSizeBits($column, $charset);
        }

        return (int) ceil($size / 8);
    }

    public function getInnodbRowSize(Table $table): int
    {
        $tableCharset = $table->hasOption('charset') ? $table->getOption('charset') : 'utf8mb4';
        $engine = $table->hasOption('engine') ? $table->getOption('engine') : 'InnoDB';
        $rowFormat = $table->hasOption('row_format') ? $table->getOption('row_format') : 'DYNAMIC';

        if ('innodb' !== strtolower($engine)) {
            throw new \InvalidArgumentException(sprintf('Invalid engine %s, only InnoDB is supported.', $engine));
        }

        // Start with 25 InnoDB extra bytes
        $size = 25 * 8;

        foreach ($table->getColumns() as $column) {
            $charset = $tableCharset;

            if ($column->toArray()['charset'] ?? null) {
                $charset = $column->toArray()['charset'];
            } elseif ($column->toArray()['collation'] ?? null) {
                $charset = explode('_', $column->toArray()['collation'])[0];
            }

            $size += $this->getInnodbColumnSizeBits($column, $charset, $rowFormat);
        }

        return (int) ceil($size / 8);
    }

    public function getMysqlRowSizeLimit(): int
    {
        return (2 ** 16) - 1;
    }

    public function getInnodbRowSizeLimit(): int
    {
        static $size = null;

        return $size ??= (int) $this->connection->executeQuery('SELECT @@innodb_page_size / 2 - 66')->fetchOne();
    }

    public function measureMysqlColumnSizeBits(Column $column, string $charset): int
    {
        $sql = $this->buildColumnSql($column, $charset);

        static $cache = [];

        if (isset($cache[$sql])) {
            return $cache[$sql];
        }

        $maxBits = $this->getMysqlRowSizeLimit() * 8;

        if (
            $this->isTableTooLarge($this->getMysqlColumnDefinitions($maxBits))
            || !$this->isTableTooLarge($this->getMysqlColumnDefinitions($maxBits + 1))
        ) {
            throw new \LogicException(sprintf('Assumed limit of %s seems to be wrong.', $maxBits / 8));
        }

        // Find the correct size using binary search
        for ($bits = $maxBits, $step = (int) ceil($bits / 2); $step > 0; $step = (int) ceil($step / 2)) {
            if (!$this->isTableTooLarge([...$this->getMysqlColumnDefinitions($bits), "testcol $sql"])) {
                $bits += $step;
            } else {
                $bits -= $step;
            }

            if (1 === $step) {
                if ($this->isTableTooLarge([...$this->getMysqlColumnDefinitions($bits), "testcol $sql"])) {
                    --$bits;
                }

                return $cache[$sql] = $maxBits - $bits;
            }
        }

        throw new \LogicException('Unable to determine the column size');
    }

    public function measureInnodbColumnSizeBits(Column $column, string $charset, string $rowFormat): int
    {
        $sql = $this->buildColumnSql($column, $charset);

        static $cache = [];

        if (isset($cache[$sql])) {
            return $cache[$sql];
        }

        $maxBits = $this->getInnodbRowSizeLimit() * 8;

        if (
            $this->isTableTooLarge($this->getInnodbColumnDefinitions($maxBits), $rowFormat)
            || !$this->isTableTooLarge($this->getInnodbColumnDefinitions($maxBits + 1), $rowFormat)
        ) {
            throw new \LogicException(sprintf('Assumed limit of %s seems to be wrong.', $maxBits / 8));
        }

        // Find the correct size using binary search
        for ($bits = $maxBits, $step = (int) ceil($bits / 2); $step > 0; $step = (int) ceil($step / 2)) {
            if (!$this->isTableTooLarge([...$this->getInnodbColumnDefinitions($bits), "testcol $sql"], $rowFormat)) {
                $bits += $step;
            } else {
                $bits -= $step;
            }

            if (1 === $step) {
                if ($this->isTableTooLarge([...$this->getInnodbColumnDefinitions($bits), "testcol $sql"], $rowFormat)) {
                    --$bits;
                }

                return $cache[$sql] = $maxBits - $bits;
            }
        }

        throw new \LogicException('Unable to determine the column size');
    }

    private function buildColumnSql(Column $column, string $charset): string
    {
        $platform = $this->connection->getDatabasePlatform();
        $columnArray = $column->toArray();

        unset($columnArray['default'], $columnArray['autoincrement']);

        $sql = $column->getType()->getSQLDeclaration($columnArray, $platform);

        if (
            0 === strncasecmp($sql, 'char', 4)
            || 0 === strncasecmp($sql, 'varchar', 7)
            || 0 === strncasecmp($sql, 'tinytext', 8)
            || 0 === strncasecmp($sql, 'text, ', 6)
            || 0 === strncasecmp($sql, 'mediumtext', 10)
            || 0 === strncasecmp($sql, 'longtext', 8)
        ) {
            $sql .= " CHARACTER SET $charset";
        }

        if ($column->getNotnull()) {
            $sql .= ' NOT';
        }

        $sql .= ' NULL';

        return $sql;
    }

    private function isTableTooLarge(array $columns, string $rowFormat = 'DYNAMIC'): bool
    {
        $testTable = 'test_'.md5(__METHOD__);
        $columns = implode(',', $columns);

        $this->connection->executeStatement("DROP TABLE IF EXISTS $testTable");
        $this->connection->executeStatement('SET SESSION innodb_strict_mode = ON');

        try {
            $this->connection->executeStatement("CREATE TABLE $testTable ($columns) ENGINE=InnoDB ROW_FORMAT=$rowFormat");
        } catch (\Throwable $exception) {
            if (false === stripos($exception->getMessage(), 'Row size too large')) {
                throw $exception;
            }

            return true;
        }

        $this->connection->executeStatement("DROP TABLE IF EXISTS $testTable");

        return false;
    }

    /**
     * @return array<int,string>
     */
    private function getMysqlColumnDefinitions(int $sizeInBits): array
    {
        if ($sizeInBits < 258 * 7 * 8) {
            throw new \InvalidArgumentException('Low bit sizes not implemented yet');
        }

        $bytes = floor($sizeInBits / 8);
        $bits = $sizeInBits % 8;

        if (0 === $bits) {
            $bytes -= 2;

            return ["col0 VARCHAR($bytes) CHARACTER SET latin1 NOT NULL"];
        }

        $columns = [];

        for ($i = 0; $i < $bits; ++$i) {
            $colSize = floor($bytes / $bits) - 2;

            if ($i === $bits - 1) {
                $colSize += $bytes % $bits;
            }
            $columns[] = "col$i VARCHAR($colSize) CHARACTER SET latin1 NULL";
        }

        return $columns;
    }

    /**
     * @return array<int,string>
     */
    private function getInnodbColumnDefinitions(int $sizeInBits): array
    {
        if ($sizeInBits < (8 * 255 + 25)) {
            throw new \InvalidArgumentException('Low bit sizes not implemented yet');
        }

        // Strip 25 InnoDB extra bytes
        $sizeInBits -= 25 * 8;

        $bytes = (int) floor($sizeInBits / 8);
        $bits = $sizeInBits % 8;
        $numberOfColumns = (int) ceil($bytes / 255);

        $columns = [];

        for ($i = 0; $i < $numberOfColumns; ++$i) {
            $colSize = (int) floor($bytes / $numberOfColumns);

            if ($i < $bytes % $numberOfColumns) {
                ++$colSize;
            }

            $null = 'NOT NULL';

            if ($numberOfColumns - 1 - $i < $bits) {
                $null = 'NULL';
            }

            $columns[] = "col$i BINARY($colSize) $null";
        }

        return $columns;
    }

    private function getMysqlColumnSizeBits(Column $column, string $charset): int
    {
        $platform = $this->connection->getDatabasePlatform();
        $bits = 0;
        $sqlDeclaration = $column->getType()->getSQLDeclaration($column->toArray(), $platform);
        $sqlType = strtoupper(preg_replace('/[^a-z].*$/is', '', $sqlDeclaration));
        $charsetMultiplier = 1;

        if ('utf8mb4' === strtolower($charset)) {
            $charsetMultiplier = 4;
        } elseif ('utf8' === strtolower($charset)) {
            $charsetMultiplier = 3;
        }

        $fixedMap = [
            'TINYINT' => 8,
            'SMALLINT' => 2 * 8,
            'INT' => 4 * 8,
            'DOUBLE' => 8 * 8,
            'DATETIME' => 5 * 8,
            'TINYBLOB' => 9 * 8,
            'BLOB' => 10 * 8,
            'MEDIUMBLOB' => 11 * 8,
            'LONGBLOB' => 12 * 8,
            'TINYTEXT' => 9 * 8,
            'TEXT' => 10 * 8,
            'MEDIUMTEXT' => 11 * 8,
            'LONGTEXT' => 12 * 8,
        ];

        $bits += $fixedMap[$sqlType] ?? 0;

        if ('VARCHAR' === $sqlType) {
            $bytes = $column->getLength() * $charsetMultiplier;

            if ($bytes > 255) {
                ++$bytes;
            }

            ++$bytes;
            $bits += 8 * $bytes;
        } elseif ('VARBINARY' === $sqlType) {
            $bytes = $column->getLength();

            if ($bytes > 255) {
                ++$bytes;
            }

            ++$bytes;
            $bits += 8 * $bytes;
        } elseif ('CHAR' === $sqlType) {
            $bits += 8 * $column->getLength() * $charsetMultiplier;
        } elseif ('BINARY' === $sqlType) {
            $bits += 8 * $column->getLength();
        }

        if (!$column->getNotnull()) {
            ++$bits;
        }

        return $bits;
    }

    private function getInnodbColumnSizeBits(Column $column, string $charset, string $rowFormat): int
    {
        if ('DYNAMIC' !== strtoupper($rowFormat)) {
            throw new \InvalidArgumentException(sprintf('Invalid row format %s, only DYNAMIC is supported.', $rowFormat));
        }

        $platform = $this->connection->getDatabasePlatform();
        $bits = 0;
        $sqlDeclaration = $column->getType()->getSQLDeclaration($column->toArray(), $platform);
        $sqlType = strtoupper(preg_replace('/[^a-z].*$/is', '', $sqlDeclaration));
        $charsetMultiplier = 1;

        if ('utf8mb4' === strtolower($charset)) {
            $charsetMultiplier = 4;
        } elseif ('utf8' === strtolower($charset)) {
            $charsetMultiplier = 3;
        }

        $fixedMap = [
            'TINYINT' => 8,
            'SMALLINT' => 2 * 8,
            'INT' => 4 * 8,
            'DOUBLE' => 8 * 8,
            'DATETIME' => 5 * 8,
            'TINYBLOB' => 41 * 8,
            'BLOB' => 41 * 8,
            'MEDIUMBLOB' => 41 * 8,
            'LONGBLOB' => 41 * 8,
            'TINYTEXT' => 41 * 8,
            'TEXT' => 41 * 8,
            'MEDIUMTEXT' => 41 * 8,
            'LONGTEXT' => 41 * 8,
        ];

        $bits += $fixedMap[$sqlType] ?? 0;

        if ('VARCHAR' === $sqlType || 'CHAR' === $sqlType) {
            $bytes = $column->getLength() * $charsetMultiplier;
            ++$bytes;
            $bits += 8 * min(41, $bytes);
        } elseif ('VARBINARY' === $sqlType) {
            $bytes = $column->getLength();
            ++$bytes;
            $bits += 8 * min(41, $bytes);
        } elseif ('BINARY' === $sqlType) {
            $bits += 8 * $column->getLength();
        }

        if (!$column->getNotnull()) {
            ++$bits;
        }

        return $bits;
    }
}
