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
    public function __construct(private readonly Connection $connection)
    {
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['prepare_for_output_encoding'])) {
            return false;
        }

        return $this->getTargets()->valid();
    }

    public function run(): MigrationResult
    {
        $converted = [];

        foreach ($this->getTargets() as [$table, $column, $virtualTarget, $options]) {
            $convertedIds = match ($options) {
                ['decodeEntities'] => $this->migrateColumn($table, $column, $virtualTarget, static fn (string $value): string => str_replace(['&#60;', '&#92;0'], ['<', '\0'], $value)),
                ['fullyEncoded'] => $this->migrateColumn(
                    $table,
                    $column,
                    $virtualTarget,
                    static function (string $value): string {
                        // Decoding JSON potentially breaks the JSON structure
                        if (json_validate($value)) {
                            return $value;
                        }

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
     * @return \Generator<int, array<int, mixed>>
     */
    private function getTargets(): \Generator
    {
        $schemaManager = $this->connection->createSchemaManager();

        foreach ($this->connection->fetchAllNumeric('SELECT table_name, column_name, virtual_target_name, encoding_options FROM prepare_for_output_encoding WHERE performed_migration = 0') as $row) {
            if (!$schemaManager->tablesExist([$row[0]]) || !$schemaManager->introspectTableByUnquotedName($row[0])->hasColumn($row[2] ?? $row[1])) {
                continue;
            }

            $row[3] = json_decode($row[3], true, flags: JSON_THROW_ON_ERROR);

            yield $row;
        }
    }

    private function migrateColumn(string $table, string $column, string|null $virtualTarget, \Closure $convert): array
    {
        $convertedIds = [];

        $dbColumn = $virtualTarget ?? $column;

        foreach ($this->connection->fetchAllKeyValue("SELECT id, `$dbColumn` FROM `$table` WHERE `$dbColumn` != ''") as $id => $dbValue) {
            if (!\is_string($dbValue)) {
                continue;
            }

            if (null !== $virtualTarget) {
                try {
                    $json = json_decode($dbValue, true, flags: JSON_THROW_ON_ERROR);
                    $value = $json[$column];
                } catch (\Throwable) {
                    continue;
                }
            } else {
                $value = $dbValue;
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

            if (null !== $virtualTarget) {
                $json[$column] = $convertedValue;
                $convertedDbValue = $this->connection->convertToDatabaseValue($json, Types::JSON);
            } else {
                $convertedDbValue = $convertedValue;
            }

            if ($dbValue !== $convertedDbValue) {
                $this->connection->update(
                    $table,
                    [$dbColumn => $convertedDbValue],
                    ['id' => $id],
                    [Types::STRING, Types::INTEGER],
                );

                $convertedIds[] = $id;
            }
        }

        return $convertedIds;
    }
}
