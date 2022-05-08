<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\DoctrineSchema;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;

/**
 * Adjusts the length of tl_search.url if innodb_large_prefix is not enabled (#4615).
 *
 * @internal
 */
class AdjustSearchUrlLengthListener
{
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function __invoke(GenerateSchemaEventArgs $event): void
    {
        // Get the tl_search table definition
        try {
            $table = $event->getSchema()->getTable('tl_search');
        } catch (SchemaException $e) {
            if (SchemaException::TABLE_DOESNT_EXIST === $e->getCode()) {
                return;
            }

            throw $e;
        }

        // Get the tl_search.url column definition
        try {
            $column = $table->getColumn('url');
        } catch (SchemaException $e) {
            if (SchemaException::COLUMN_DOESNT_EXIST === $e->getCode()) {
                return;
            }

            throw $e;
        }

        // Check if the field has an index
        try {
            $table->getIndex('url');
        } catch (SchemaException $e) {
            if (SchemaException::INDEX_DOESNT_EXIST === $e->getCode()) {
                return;
            }

            throw $e;
        }

        // Get maximum index size for this table
        $maximumIndexSize = $this->getMaximumIndexSize($table);

        // Reduce maximum index size if collation is not "ascii_bin"
        if ('ascii_bin' !== $column->getPlatformOption('collation')) {
            $bytesPerChar = 'utf8mb4' === $table->getOption('charset') ? 4 : 3;
            $maximumIndexSize = floor($maximumIndexSize / $bytesPerChar);
        }

        if ($column->getLength() <= $maximumIndexSize) {
            return;
        }

        // Set the length
        $column->setLength($maximumIndexSize);
    }

    private function getMaximumIndexSize(Table $table): int
    {
        $engine = $table->getOption('engine');

        if ('innodb' !== strtolower($engine)) {
            return 1000;
        }

        // The row format is not DYNAMIC or COMPRESSED
        if (!\in_array($table->getOption('row_format'), ['DYNAMIC', 'COMPRESSED'], true)) {
            return 767;
        }

        $largePrefix = $this->connection->fetchAssociative("SHOW VARIABLES LIKE 'innodb_large_prefix'");

        // The variable no longer exists as of MySQL 8 and MariaDB 10.3
        if (false === $largePrefix || '' === $largePrefix['Value']) {
            return 3072;
        }

        $version = $this->connection->fetchAssociative('SELECT @@version as Value');

        [$ver] = explode('-', $version['Value']);

        // As there is no reliable way to get the vendor (see #84), we are
        // guessing based on the version number. The check will not be run
        // as of MySQL 8 and MariaDB 10.3, so this should be safe.
        $vok = version_compare($ver, '10', '>=') ? '10.2.2' : '5.7.7';

        // Large prefixes are always enabled as of MySQL 5.7.7 and MariaDB 10.2.2
        if (version_compare($ver, $vok, '>=')) {
            return 3072;
        }

        // The innodb_large_prefix option is disabled
        if (!\in_array(strtolower((string) $largePrefix['Value']), ['1', 'on'], true)) {
            return 767;
        }

        return 3072;
    }
}
