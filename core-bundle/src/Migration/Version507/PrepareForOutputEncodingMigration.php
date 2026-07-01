<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration\Version507;

use Contao\Controller;
use Contao\CoreBundle\Config\ResourceFinder;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\DcaExtractor;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\BinaryType;
use Doctrine\DBAL\Types\BlobType;
use Doctrine\DBAL\Types\JsonType;
use Doctrine\DBAL\Types\SimpleArrayType;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\TextType;
use Doctrine\DBAL\Types\Types;

class PrepareForOutputEncodingMigration extends AbstractMigration
{
    public function __construct(
        private readonly Connection $connection,
        private readonly ContaoFramework $framework,
        private readonly ResourceFinder $resourceFinder,
    ) {
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['prepare_for_output_encoding'])) {
            return false;
        }

        $targets = $this->getTargets();

        $count = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM prepare_for_output_encoding');

        if ($count !== \count($targets)) {
            return true;
        }

        foreach ($targets as [$table, $column, $virtualTarget, $options]) {
            $test = $this->connection->fetchOne(
                <<<'SQL'
                    SELECT TRUE FROM prepare_for_output_encoding
                    WHERE table_name = :table
                    AND column_name = :column
                    AND virtual_target_name <=> :virtualTarget
                    AND encoding_options = CAST(:options as JSON)
                SQL,
                [
                    'table' => $table,
                    'column' => $column,
                    'virtualTarget' => $virtualTarget,
                    'options' => $options,
                ],
                [
                    'table' => Types::STRING,
                    'column' => Types::STRING,
                    'virtualTarget' => Types::STRING,
                    'options' => Types::JSON,
                ],
            );

            if (false === $test) {
                return true;
            }
        }

        return false;
    }

    public function run(): MigrationResult
    {
        $this->connection->executeStatement('TRUNCATE TABLE prepare_for_output_encoding');

        foreach ($this->getTargets() as [$table, $column, $virtualTarget, $options]) {
            $this->connection->insert(
                'prepare_for_output_encoding',
                [
                    'table_name' => $table,
                    'column_name' => $column,
                    'virtual_target_name' => $virtualTarget,
                    'encoding_options' => $options,
                    'performed_migration' => false,
                ],
                [
                    'table_name' => Types::STRING,
                    'column_name' => Types::STRING,
                    'virtual_target_name' => Types::STRING,
                    'encoding_options' => Types::JSON,
                    'performed_migration' => Types::BOOLEAN,
                ],
            );
        }

        return $this->createResult(true);
    }

    /**
     * @return list<array{0: string, 1: string, 2: string|null, 3: array}>
     */
    private function getTargets(): array
    {
        $this->framework->initialize();

        $includeFields = [
            'tl_log' => [
                'text' => true,
            ],
            'tl_version' => [
                'description' => true,
            ],
        ];

        $excludeFields = [
            'tl_files' => [
                'name' => true,
            ],
        ];

        $schemaManager = $this->connection->createSchemaManager();
        $targets = [];
        $processed = [];

        $files = $this->resourceFinder->findIn('dca')->depth(0)->files()->name('*.php');

        foreach ($files as $file) {
            $tableName = $file->getBasename('.php');

            if (\in_array($tableName, $processed, true)) {
                continue;
            }

            $processed[] = $tableName;

            if (!$schemaManager->tablesExist([$tableName])) {
                continue;
            }

            $columns = $schemaManager->listTableColumns($tableName);

            Controller::loadDataContainer($tableName);
            $virtualFields = DcaExtractor::getInstance($tableName)->getVirtualFields();

            foreach ($GLOBALS['TL_DCA'][$tableName]['fields'] ?? [] as $fieldName => $fieldConfig) {
                $virtualTargetColumn = $virtualFields[$fieldName] ?? null;

                if ($excludeFields[$tableName][$fieldName] ?? null) {
                    continue;
                }

                if (!$virtualTargetColumn) {
                    $fieldName = strtolower($fieldName);
                    $field = $columns[$fieldName] ?? $columns["`$fieldName`"] ?? null;

                    if (!$field) {
                        continue;
                    }

                    $type = $field->getType();

                    if (
                        !$type instanceof StringType
                        && !$type instanceof BinaryType
                        && !$type instanceof BlobType
                        && !$type instanceof JsonType
                        && !$type instanceof SimpleArrayType
                        && !$type instanceof TextType
                    ) {
                        continue;
                    }
                }

                $options = $this->getEncodingOptions($fieldConfig, $includeFields[$tableName][$fieldName] ?? false);

                if (!$options) {
                    continue;
                }

                $targets[] = [$tableName, $fieldName, $virtualTargetColumn, $options];
            }
        }

        return $targets;
    }

    private function getEncodingOptions(array $fieldConfig, bool $force): array
    {
        if (
            !$force
            && \in_array(
                $fieldConfig['inputType'] ?? null,
                [
                    null,
                    'select',
                    'radio',
                    'radioTable',
                    'checkbox',
                    'checkboxWizard',
                    'picker',
                    'pageTree',
                    'fileTree',
                    'fileUpload',
                    'moduleWizard',
                    'sectionWizard',
                    'chmod',
                    'cud',
                    'imageSize',
                ],
                true,
            )
        ) {
            return [];
        }

        if (
            ($fieldConfig['eval']['useRawRequestData'] ?? null)
            || ($fieldConfig['eval']['allowHtml'] ?? null)
            || ($fieldConfig['eval']['preserveTags'] ?? null)
            || 'ace|html' === ($fieldConfig['eval']['rte'] ?? null)
            || str_starts_with($fieldConfig['eval']['rte'] ?? '', 'tiny')
        ) {
            return [];
        }

        if ($fieldConfig['eval']['decodeEntities'] ?? null) {
            return ['decodeEntities'];
        }

        return ['fullyEncoded'];
    }
}
