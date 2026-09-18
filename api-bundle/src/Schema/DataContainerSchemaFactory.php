<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Schema;

use Contao\ApiBundle\Dto\DataContainerMove;
use Contao\ApiBundle\Dto\DataContainerRecord;
use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Widget\ApiWidgetInterface;
use Contao\Validator as ContaoValidator;
use Contao\Widget;

final class DataContainerSchemaFactory
{
    public function __construct(private readonly ContaoFramework $framework)
    {
    }

    /**
     * @return array{
     *     type: 'object',
     *     properties: array<string, array<string, mixed>>,
     *     additionalProperties: bool
     * }
     */
    public function create(string $table): array
    {
        $this->framework->initialize();
        $this->framework->getAdapter(Controller::class)->loadDataContainer($table);

        $properties = [];

        foreach ($GLOBALS['TL_DCA'][$table]['fields'] ?? [] as $fieldName => $config) {
            $schema = $this->createFieldSchema((string) $fieldName, \is_array($config) ? $config : []);

            if ([] === $schema) {
                continue;
            }

            $properties[$fieldName] = $schema;
        }

        // Mandatory fields depend on the active palette and are validated by the widgets
        return [
            'type' => 'object',
            'properties' => $properties,
            'additionalProperties' => false,
        ];
    }

    public function createOperationSchemas(string $table): array
    {
        $schema = $this->create($table);

        return [
            'read' => $this->projectSchema($schema, 'read'),
            'create' => $this->projectSchema($schema, 'create'),
            'update' => $this->projectSchema($schema, 'update'),
            'move' => DataContainerMove::SCHEMA,
        ];
    }

    private function projectSchema(array $schema, string $operation): array
    {
        foreach ($schema['properties'] as $field => $property) {
            $excluded = 'read' === $operation
                ? ($property['writeOnly'] ?? false)
                : ($property['readOnly'] ?? false) || \in_array($field, 'update' === $operation ? ['pid', 'ptable', 'sorting'] : ['sorting'], true);

            if ($excluded) {
                unset($schema['properties'][$field]);
            }
        }

        if ('create' === $operation) {
            foreach (['pid' => 'Destination parent ID for the new record.', 'ptable' => 'Destination parent table for the new record.'] as $field => $description) {
                if (isset($schema['properties'][$field])) {
                    $schema['properties'][$field]['description'] = $description;
                }
            }
        }

        if ([] === $schema['properties']) {
            unset($schema['properties']);
        }

        return $schema;
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    private function createFieldSchema(string $fieldName, array $config): array
    {
        $widget = $GLOBALS['BE_FFL'][$config['inputType'] ?? ''] ?? null;
        $supported = \is_string($widget) && is_a($widget, Widget::class, true) && is_a($widget, ApiWidgetInterface::class, true);

        if (!$supported && (isset($config['inputType']) || !\in_array($fieldName, DataContainerRecord::METADATA_FIELDS, true))) {
            return [];
        }

        $sql = \is_array($config['sql'] ?? null) ? $config['sql'] : [];
        $schema = $this->createValueSchema($config, $sql);

        if ($config['eval']['multiple'] ?? false) {
            $schema = ['type' => 'array', 'items' => $schema ?: ['type' => 'string']];
        }

        if ($supported) {
            $schema = $widget::getApiSchema($config, $schema);
        }

        $schema = array_replace($schema, $config['api']['schema'] ?? []);

        if (\in_array($fieldName, ['id', 'tstamp'], true) || ($config['eval']['readonly'] ?? false) || ($config['eval']['disabled'] ?? false)) {
            $schema['readOnly'] = true;
        }

        if (\in_array($fieldName, ['pid', 'ptable', 'sorting'], true)) {
            $schema['description'] = trim(($schema['description'] ?? '').' Use the move operation to change the parent or position of an existing record.');
        }

        if ('id' === $fieldName) {
            $schema['type'] = 'integer';
        }

        return $schema;
    }

    private function createValueSchema(array $config, array $sql): array
    {
        $schema = [];
        $eval = $config['eval'] ?? [];

        if (null !== ($type = $this->guessType($config, $sql))) {
            $schema['type'] = $type;
        }

        if ('string' === ($schema['type'] ?? null)) {
            $schema += $this->createStringSchema($config, $sql);
        }

        if ($choices = $this->normalizeChoices($config['options'] ?? null)) {
            $schema['enum'] = $choices;
        }

        if (isset($eval['rgxp'])) {
            $schema += $this->getFormatForRgxp((string) $eval['rgxp']);
        }

        if (isset($sql['default'])) {
            $schema['default'] = $sql['default'];
        }

        return $schema;
    }

    private function createStringSchema(array $config, array $sql): array
    {
        $schema = [];
        $eval = $config['eval'] ?? [];

        if (isset($sql['length']) && \is_int($sql['length'])) {
            $schema['maxLength'] = $sql['length'];
        }

        if (isset($eval['maxlength']) && is_numeric($eval['maxlength'])) {
            $schema['maxLength'] = (int) $eval['maxlength'];
        }

        if (isset($eval['minlength']) && is_numeric($eval['minlength'])) {
            $schema['minLength'] = (int) $eval['minlength'];
        }

        return $schema;
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $sql
     */
    private function guessType(array $config, array $sql): string|null
    {
        if (isset($sql['type'])) {
            return match ($sql['type']) {
                'boolean' => 'boolean',
                'integer', 'smallint', 'bigint' => 'integer',
                'decimal', 'float', 'double', 'numeric', 'real' => 'number',
                'array', 'json' => 'array',
                default => 'string',
            };
        }

        if (isset($config['options']) && \is_array($config['options'])) {
            return 'string';
        }

        return null;
    }

    /**
     * @return list<scalar>
     */
    private function normalizeChoices(mixed $choices): array
    {
        if (!\is_array($choices)) {
            return [];
        }

        $normalized = [];

        foreach ($choices as $choice) {
            if (\is_array($choice)) {
                continue;
            }

            if (null === $choice || \is_object($choice)) {
                continue;
            }

            $normalized[] = $choice;
        }

        return $normalized;
    }

    /**
     * @return array<string, mixed>
     */
    private function getFormatForRgxp(string $rgxp): array
    {
        return match ($rgxp) {
            'email' => ['format' => 'email'],
            'url' => ['format' => 'uri'],
            'digit' => ['pattern' => ContaoValidator::REGEXP_DIGIT],
            'alpha' => ['pattern' => ContaoValidator::REGEXP_ALPHA],
            'alnum' => ['pattern' => ContaoValidator::REGEXP_ALNUM],
            default => [],
        };
    }
}
