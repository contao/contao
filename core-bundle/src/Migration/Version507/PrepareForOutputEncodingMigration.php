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

        foreach ($targets as [$table, $column, $options]) {
            $test = $this->connection->fetchOne(
                '
                    SELECT TRUE FROM prepare_for_output_encoding
                    WHERE table_name = :table
                    AND column_name = :column
                    AND encoding_options = CAST(:options as JSON)
                ',
                [
                    'table' => $table,
                    'column' => $column,
                    'options' => $options,
                ],
                [
                    'table' => Types::STRING,
                    'column' => Types::STRING,
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

        foreach ($this->getTargets() as [$table, $column, $options]) {
            $this->connection->insert(
                'prepare_for_output_encoding',
                [
                    'table_name' => $table,
                    'column_name' => $column,
                    'encoding_options' => $options,
                    'performed_migration' => false,
                ],
                [
                    'table_name' => Types::STRING,
                    'column_name' => Types::STRING,
                    'encoding_options' => Types::JSON,
                    'performed_migration' => Types::BOOLEAN,
                ],
            );
        }

        return $this->createResult(true);
    }

    /**
     * @return list<array{0: string, 1: string, 2: array}>
     */
    private function getTargets(): array
    {
        $this->framework->initialize();

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

            foreach ($GLOBALS['TL_DCA'][$tableName]['fields'] ?? [] as $fieldName => $fieldConfig) {
                $fieldName = strtolower($fieldName);
                $field = $columns[$fieldName] ?? $columns["`$fieldName`"] ?? null;

                if (
                    !$field?->getType() instanceof StringType
                    && !$field?->getType() instanceof BinaryType
                    && !$field?->getType() instanceof BlobType
                    && !$field?->getType() instanceof JsonType
                    && !$field?->getType() instanceof SimpleArrayType
                    && !$field?->getType() instanceof TextType
                ) {
                    continue;
                }

                $options = $this->getEncodingOptions($fieldConfig);

                if (!$options) {
                    continue;
                }

                $targets[] = [$tableName, $fieldName, $options];
            }
        }

        return $targets;
    }

    private function getEncodingOptions(array $fieldConfig): array
    {
        if (
            \in_array(
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
            || ($fieldConfig['eval']['rte'] ?? null) === 'ace|html'
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
