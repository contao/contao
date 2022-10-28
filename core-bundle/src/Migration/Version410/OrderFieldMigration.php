<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration\Version410;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
class OrderFieldMigration extends AbstractMigration
{
    private const ORDER_FIELDS = [
        'tl_calendar_events' => [
            'orderEnclosure' => 'enclosure',
        ],
        'tl_layout' => [
            'orderExt' => 'external',
            'orderExtJs' => 'externalJs',
        ],
        'tl_module' => [
            'orderPages' => 'pages',
        ],
        'tl_faq' => [
            'orderEnclosure' => 'enclosure',
        ],
        'tl_news' => [
            'orderEnclosure' => 'enclosure',
        ],
    ];

    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        foreach (self::ORDER_FIELDS as $table => $fields) {
            if (!$schemaManager->tablesExist($table)) {
                continue;
            }

            $columns = $schemaManager->listTableColumns($table);

            foreach ($fields as $orderField => $field) {
                if (isset($columns[strtolower($orderField)], $columns[strtolower($field)])) {
                    return true;
                }
            }
        }

        return false;
    }

    public function run(): MigrationResult
    {
        $schemaManager = $this->connection->createSchemaManager();

        foreach (self::ORDER_FIELDS as $table => $fields) {
            if (!$schemaManager->tablesExist($table)) {
                continue;
            }

            $columns = $schemaManager->listTableColumns($table);

            foreach ($fields as $orderField => $field) {
                if (isset($columns[strtolower($orderField)], $columns[strtolower($field)])) {
                    $this->migrateOrderField($table, $orderField, $field);
                }
            }
        }

        return $this->createResult(true);
    }

    private function migrateOrderField(string $table, string $orderField, string $field): void
    {
        $tableQuoted = $this->connection->quoteIdentifier($table);
        $orderFieldQuoted = $this->connection->quoteIdentifier($orderField);
        $fieldQuoted = $this->connection->quoteIdentifier($field);

        $rows = $this->connection->fetchAllAssociative("
            SELECT
                $orderFieldQuoted, $fieldQuoted
            FROM
                $tableQuoted
            WHERE
                $orderFieldQuoted IS NOT NULL
                AND $orderFieldQuoted != ''
                AND $fieldQuoted IS NOT NULL
                AND $fieldQuoted != ''
        ");

        foreach ($rows as $row) {
            $items = StringUtil::deserialize($row[$field], true);
            $order = array_map(static function (): void {}, array_flip(StringUtil::deserialize($row[$orderField], true)));

            foreach ($items as $key => $value) {
                if (\array_key_exists($value, $order)) {
                    $order[$value] = $value;
                    unset($items[$key]);
                }
            }

            $items = array_merge(array_values(array_filter($order)), array_values($items));

            $this->connection->executeStatement(
                "
                    UPDATE $tableQuoted
                    SET $fieldQuoted = :items, $orderFieldQuoted = ''
                    WHERE $fieldQuoted = :field AND $orderFieldQuoted = :orderField
                ",
                [
                    'items' => serialize($items),
                    'field' => $row[$field],
                    'orderField' => $row[$orderField],
                ]
            );
        }

        $this->connection->executeStatement("ALTER TABLE $tableQuoted DROP $orderFieldQuoted");
    }
}
