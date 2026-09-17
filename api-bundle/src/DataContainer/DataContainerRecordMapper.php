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
use Contao\CoreBundle\Widget\ApiWidgetInterface;
use Contao\Widget;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Each mapping method builds the schema first, which initializes the framework
 * and loads the DCA before accessing its field configuration. The caller provides
 * the request and DC_Table context for onload callbacks.
 */
final class DataContainerRecordMapper
{
    public function __construct(private readonly DataContainerSchemaFactory $schemaFactory)
    {
    }

    public function fromRow(string $table, array $row): DataContainerRecord
    {
        $data = [];

        foreach ($this->schemaFactory->create($table)['properties'] as $field => $schema) {
            if ('id' === $field || !\array_key_exists($field, $row) || ($schema['writeOnly'] ?? false)) {
                continue;
            }

            $config = $GLOBALS['TL_DCA'][$table]['fields'][$field] ?? [];
            $widget = $this->getWidget($config);

            if (null !== $widget) {
                $data[$field] = $widget::convertToApiValue($row[$field], $config, $schema);
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
            $widget = $this->getWidget($config);

            if (null === $widget) {
                throw new UnprocessableEntityHttpException('Field "'.$field.'" has no API-capable widget.');
            }

            $form[$field] = $widget::convertToApiFormValue($value, $config, $schema);
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
            $widget = $this->getWidget($config);

            if (($config['eval']['readonly'] ?? false) || ($config['eval']['disabled'] ?? false)) {
                continue;
            }

            if (null === $widget && ($config['eval']['mandatory'] ?? false)) {
                throw new UnprocessableEntityHttpException('Required field "'.$field.'" has no API-capable widget.');
            }

            if (null === $schema || null === $widget) {
                continue;
            }

            // Never submit a stored secret as a new password or other write-only value
            $value = $schema['writeOnly'] ?? false ? null : $widget::convertToApiValue($row[$field] ?? null, $config, $schema);
            $form[$field] = $widget::convertToApiFormValue($value, $config, $schema);
        }

        return $form;
    }

    /**
     * @return class-string<Widget&ApiWidgetInterface>|null
     */
    private function getWidget(array $config): string|null
    {
        $widget = $GLOBALS['BE_FFL'][$config['inputType'] ?? ''] ?? null;

        return \is_string($widget) && is_a($widget, Widget::class, true) && is_a($widget, ApiWidgetInterface::class, true) ? $widget : null;
    }
}
