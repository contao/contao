<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\DataContainer;

use Contao\ApiBundle\Dto\DataContainerRecord;
use Contao\ApiBundle\Schema\DataContainerSchemaFactory;
use Contao\ApiBundle\Widget\WidgetConverterRegistry;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Each mapping method builds the schema first, which initializes the framework
 * and loads the DCA before accessing its field configuration. The caller provides
 * the request and DC_Table context for onload callbacks.
 */
final class DataContainerRecordMapper
{
    public function __construct(
        private readonly DataContainerSchemaFactory $schemaFactory,
        private readonly WidgetConverterRegistry $converters,
    ) {
    }

    public function fromRow(string $table, array $row): DataContainerRecord
    {
        $data = [];

        foreach ($this->schemaFactory->create($table)['properties'] as $field => $schema) {
            if ('id' === $field || !\array_key_exists($field, $row) || ($schema['writeOnly'] ?? false)) {
                continue;
            }

            $config = $GLOBALS['TL_DCA'][$table]['fields'][$field] ?? [];
            $converter = $this->converters->get($config);

            if ($converter) {
                $data[$field] = $converter->convertToApiValue($row[$field], $config, $schema);
            } elseif (!isset($config['inputType']) && \in_array($field, DataContainerRecord::METADATA_FIELDS, true)) {
                $data[$field] = null === $row[$field] ? null : ('ptable' === $field ? (string) $row[$field] : (int) $row[$field]);
            }
        }

        return new DataContainerRecord($table, $data, $row['id']);
    }

    public function toFormValues(string $table, array $values): array
    {
        $form = [];
        $properties = $this->schemaFactory->createOperationSchemas($table)['update']['properties'] ?? [];

        foreach ($values as $field => $value) {
            $schema = $properties[$field] ?? null;

            if (!$schema || ($schema['readOnly'] ?? false)) {
                throw new UnprocessableEntityHttpException('Field "'.$field.'" is not writable through the update operation. Check the resource description for supported operations.');
            }

            $config = $GLOBALS['TL_DCA'][$table]['fields'][$field] ?? [];
            $converter = $this->converters->get($config);

            if (!$converter) {
                throw new UnprocessableEntityHttpException('Field "'.$field.'" has no API-capable widget.');
            }

            $form[$field] = $converter->convertToFormValue($value, $config, $schema);
        }

        return $form;
    }

    public function toFormDefaults(string $table, array $row, array $fields): array
    {
        $form = [];
        $properties = $this->schemaFactory->createOperationSchemas($table)['update']['properties'] ?? [];

        foreach ($fields as $field) {
            $config = $GLOBALS['TL_DCA'][$table]['fields'][$field] ?? [];
            $schema = $properties[$field] ?? null;
            $converter = $this->converters->get($config);

            if (($config['eval']['readonly'] ?? false) || ($config['eval']['disabled'] ?? false)) {
                continue;
            }

            if (!$converter && ($config['eval']['mandatory'] ?? false)) {
                throw new UnprocessableEntityHttpException('Required field "'.$field.'" has no API-capable widget.');
            }

            if (null === $schema || !$converter) {
                continue;
            }

            // Never submit a stored secret as a new password or other write-only value
            $value = $schema['writeOnly'] ?? false ? null : $converter->convertToApiValue($row[$field] ?? null, $config, $schema);
            $form[$field] = $converter->convertToFormValue($value, $config, $schema);
        }

        return $form;
    }
}
