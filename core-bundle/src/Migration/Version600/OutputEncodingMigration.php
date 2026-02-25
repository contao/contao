<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration\Version600;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

class OutputEncodingMigration extends AbstractMigration
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['prepare_for_output_encoding'])) {
            return false;
        }

        $count = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM prepare_for_output_encoding WHERE performed_migration = 0');

        return $count > 0;
    }

    public function run(): MigrationResult
    {
        $converted = [];

        foreach ($this->getTargets() as [$table, $column, $options]) {
            $convertedIds = match ($options) {
                ['decodeEntities'] => $this->migrateColumn($table, $column, static fn (string $value): string => str_replace(['&#60;', '&#92;0'], ['<', '\0'], $value)),
                ['fullyEncoded'] => $this->migrateColumn(
                    $table,
                    $column,
                    static function (string $value): string {
                        $value = str_replace(['&#123;&#123;', '&#125;&#125;'], ['[{]', '[}]'], $value);

                        return html_entity_decode($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
                    },
                ),
                default => throw new \LogicException(\sprintf('Unexpected encoding options %s', json_encode($options, JSON_THROW_ON_ERROR))),
            };

            if ($convertedIds) {
                array_push($converted, ...array_map(static fn ($id) => "$table.$id.$column", $convertedIds));
            }

            $this->connection->update('prepare_for_output_encoding', ['performed_migration' => 1], ['table_name' => $table, 'column_name' => $column]);
        }

        natsort($converted);

        return $this->createResult(true, "{$this->getName()} executed successfully: ".implode(', ', $converted));
    }

    /**
     * @return list<array{0: string, 1: string, 2: array}>
     */
    private function getTargets(): array
    {
        return array_map(
            static function ($row) {
                $row[2] = json_decode($row[2], true, flags: JSON_THROW_ON_ERROR);

                return $row;
            },
            $this->connection->fetchAllNumeric('SELECT table_name, column_name, encoding_options FROM prepare_for_output_encoding WHERE performed_migration = 0'),
        );
    }

    private function migrateColumn(string $table, string $column, \Closure $convert): array
    {
        $convertedIds = [];

        foreach ($this->connection->fetchAllKeyValue("SELECT id, `$column` FROM `$table` WHERE `$column` != ''") as $id => $value) {
            if (!\is_string($value)) {
                continue;
            }

            $convertedValue = StringUtil::deserialize($value);

            if (\is_string($convertedValue)) {
                $convertedValue = $convert($convertedValue);
            } elseif (\is_array($convertedValue)) {
                array_walk_recursive(
                    $convertedValue,
                    static function (&$val) use ($convert): void {
                        if (\is_string($val)) {
                            $val = $convert($val);
                        }
                    },
                );
                $convertedValue = serialize($convertedValue);
            } else {
                continue;
            }

            if ($value !== $convertedValue) {
                $this->connection->update(
                    $table,
                    [$column => $convertedValue],
                    ['id' => $id, $column => $value],
                    [Types::STRING, Types::INTEGER, Types::STRING],
                );

                $convertedIds[] = $id;
            }
        }

        return $convertedIds;
    }
}
