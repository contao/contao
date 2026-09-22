<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Api\Widget;

use Contao\ApiBundle\Schema\DataContainerSchemaFactory;
use Contao\ApiBundle\Widget\WidgetConverterInterface;
use Contao\ApiBundle\Widget\WidgetConverterRegistry;
use Contao\RowWizard;
use Contao\StringUtil;

final class RowWizardConverter implements WidgetConverterInterface
{
    public function __construct(
        private readonly WidgetConverterRegistry $converters,
        private readonly DataContainerSchemaFactory $schemaFactory,
    ) {
    }

    public function supports(array $config): bool
    {
        $widget = $GLOBALS['BE_FFL'][$config['inputType'] ?? ''] ?? null;

        if (!\is_string($widget) || !is_a($widget, RowWizard::class, true)) {
            return false;
        }

        // A row is replaced as a whole, so omitting unsupported cells would lose their values
        return !array_any($this->getFields($config), fn (array $field): bool => !$this->converters->get($field));
    }

    public function getSchema(array $config, array $schema): array
    {
        $properties = [];
        $readOnly = false;

        foreach ($this->getFields($config) as $name => $field) {
            $properties[$name] = $this->schemaFactory->createWidgetSchema($field);
            $readOnly = $readOnly || ($properties[$name]['readOnly'] ?? false);
        }

        if ($this->hasEnableAction($config)) {
            $properties['enable'] = ['type' => 'boolean'];
        }

        $schema = ['type' => 'array', 'items' => ['type' => 'object', 'additionalProperties' => false]];

        if ([] !== $properties) {
            $schema['items']['properties'] = $properties;
        }

        // Read-only cells cannot be preserved when rows are added, removed or reordered
        if ($readOnly) {
            $schema['readOnly'] = true;
        }

        return $schema;
    }

    public function convertToApiValue(mixed $value, array $config, array $schema): array
    {
        $rows = match (true) {
            null === $value || '' === $value => [],
            !\is_string($value) => $value,
            'json' === ($config['sql']['type'] ?? null) => json_decode($value, true, 512, JSON_THROW_ON_ERROR),
            default => StringUtil::deserialize($value, true),
        };

        return array_map(
            function ($row) use ($config, $schema): object {
                $row = (array) $row;
                $converted = [];

                foreach ($this->getFields($config) as $name => $field) {
                    $property = $schema['items']['properties'][$name] ?? [];

                    if ($property['writeOnly'] ?? false) {
                        continue;
                    }

                    $converted[$name] = $this->converters->get($field)?->convertToApiValue($row[$name] ?? null, $field, $property);
                }

                if ($this->hasEnableAction($config)) {
                    $converted['enable'] = (bool) ($row['enable'] ?? false);
                }

                return (object) $converted;
            },
            array_values($rows),
        );
    }

    public function convertToFormValue(mixed $value, array $config, array $schema): array
    {
        $rows = array_values((array) $value);
        // RowWizard validates as many rows as there are hidden _rows inputs
        $converted = ['_rows' => array_fill(0, \count($rows), '1')];

        foreach ($rows as $index => $row) {
            $row = (array) $row;
            $converted[$index] = [];

            foreach ($this->getFields($config) as $name => $field) {
                $property = $schema['items']['properties'][$name] ?? [];

                if ($property['readOnly'] ?? false) {
                    continue;
                }

                $converted[$index][$name] = $this->converters->get($field)?->convertToFormValue($row[$name] ?? null, $field, $property);
            }

            if ($this->hasEnableAction($config)) {
                $converted[$index]['enable'] = $row['enable'] ?? false ? '1' : '';
            }
        }

        return $converted;
    }

    private function getFields(array $config): array
    {
        // RowWizard::getAttributesFromDca() gives top-level fields precedence over eval.fields
        return $config['fields'] ?? $config['eval']['fields'] ?? [];
    }

    private function hasEnableAction(array $config): bool
    {
        return \in_array('enable', $config['eval']['actions'] ?? [], true);
    }
}
