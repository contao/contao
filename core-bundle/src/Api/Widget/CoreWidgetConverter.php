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

use Contao\ApiBundle\Widget\WidgetConverterInterface;
use Contao\CheckBox;
use Contao\CheckBoxWizard;
use Contao\ChmodTable;
use Contao\CoreBundle\Widget\DateValueFormatter;
use Contao\CudTable;
use Contao\FileTree;
use Contao\ImageSize;
use Contao\InputUnit;
use Contao\KeyValueWizard;
use Contao\ListWizard;
use Contao\MetaWizard;
use Contao\ModuleWizard;
use Contao\OptionWizard;
use Contao\PageTree;
use Contao\Password;
use Contao\Picker;
use Contao\RadioButton;
use Contao\RadioTable;
use Contao\RootPageDependentSelect;
use Contao\SectionWizard;
use Contao\SelectMenu;
use Contao\StringUtil;
use Contao\TableWizard;
use Contao\TextArea;
use Contao\TextField;
use Contao\TimePeriod;

final class CoreWidgetConverter implements WidgetConverterInterface
{
    // Match RootPageDependentSelect before SelectMenu because its value is a map.
    // RowWizard has its own converter for delegating to child widgets. Uploads need
    // file requests and SerpPreview has no submitted value.
    private const WIDGET_TYPES = [
        CheckBoxWizard::class,
        ChmodTable::class,
        CudTable::class,
        ImageSize::class,
        InputUnit::class,
        KeyValueWizard::class,
        ListWizard::class,
        MetaWizard::class,
        ModuleWizard::class,
        OptionWizard::class,
        Picker::class,
        RadioTable::class,
        RootPageDependentSelect::class,
        SectionWizard::class,
        TableWizard::class,
        TimePeriod::class,
        FileTree::class,
        PageTree::class,
        CheckBox::class,
        Password::class,
        TextField::class,
        TextArea::class,
        SelectMenu::class,
        RadioButton::class,
    ];

    public function __construct(private readonly DateValueFormatter $dateValueFormatter)
    {
    }

    public function supports(array $config): bool
    {
        return null !== $this->getWidgetType($config);
    }

    public function getSchema(array $config, array $schema): array
    {
        if (null !== ($structured = $this->getStructuredSchema($config))) {
            return $structured;
        }

        $multiple = $config['eval']['multiple'] ?? false;

        return match ($this->getWidgetType($config)) {
            FileTree::class => $this->getFileTreeSchema($schema, $multiple),
            PageTree::class, Picker::class => array_replace($schema, $multiple ? ['type' => 'array', 'items' => ['type' => 'integer']] : ['type' => 'integer']),
            CheckBoxWizard::class => ['type' => 'array', 'items' => $schema['items'] ?? $schema ?: ['type' => 'string']],
            RadioTable::class => $schema ?: ['type' => 'string'],
            CheckBox::class => $multiple ? $schema : $this->getBooleanSchema($schema),
            Password::class => array_replace($schema, ['writeOnly' => true]),
            default => array_replace(['type' => 'string'], $schema),
        };
    }

    public function convertToApiValue(mixed $value, array $config, array $schema): mixed
    {
        if (null !== $this->getStructuredSchema($config)) {
            return $this->convertStructure($this->decodeArray($value, $config), $schema, false);
        }

        if ('array' === ($schema['type'] ?? null)) {
            $values = $this->decodeArray($value, $config);

            return array_map(fn ($item) => $this->convertToApiValue($item, $config, $schema['items'] ?? []), $values);
        }

        $value = match ($schema['type'] ?? null) {
            'boolean' => (bool) $value,
            'integer' => (int) $value,
            'number' => (float) $value,
            'string' => (string) $value,
            default => $value,
        };

        return match ($this->getWidgetType($config)) {
            FileTree::class => $this->convertFileReference($value, false !== ($config['eval']['binary'] ?? true)),
            default => $value,
        };
    }

    public function convertToFormValue(mixed $value, array $config, array $schema): mixed
    {
        if (null !== $this->getStructuredSchema($config)) {
            return $this->convertStructure($value, $schema, true);
        }

        if (null === $value || false === $value) {
            return '';
        }

        if (\is_array($value)) {
            $values = array_map(fn ($item) => $this->convertToFormValue($item, $config, $schema['items'] ?? []), $value);

            return match ($this->getWidgetType($config)) {
                FileTree::class, PageTree::class, Picker::class => implode(',', $values),
                default => $values,
            };
        }

        return $this->dateValueFormatter->format($value, $config['eval']['rgxp'] ?? '') ?? (string) $value;
    }

    private function getStructuredSchema(array $config): array|null
    {
        $string = ['type' => 'string'];
        $strings = ['type' => 'array', 'items' => $string];

        return match ($this->getWidgetType($config)) {
            ListWizard::class, ChmodTable::class, CudTable::class => $strings,
            TableWizard::class => ['type' => 'array', 'items' => $strings],
            InputUnit::class, TimePeriod::class => $this->getObjectSchema(['value' => 'string', 'unit' => 'string']),
            KeyValueWizard::class => ['type' => 'array', 'items' => $this->getObjectSchema(['key' => 'string', 'value' => 'string'])],
            OptionWizard::class => ['type' => 'array', 'items' => $this->getObjectSchema(['value' => 'string', 'label' => 'string', 'default' => 'boolean', 'group' => 'boolean'])],
            ModuleWizard::class => ['type' => 'array', 'items' => $this->getObjectSchema(['mod' => 'string', 'col' => 'string', 'enable' => 'boolean'])],
            SectionWizard::class => ['type' => 'array', 'items' => $this->getObjectSchema(['title' => 'string', 'id' => 'string', 'template' => 'string', 'position' => 'string'])],
            MetaWizard::class => ['type' => 'object', 'additionalProperties' => ['type' => 'object', 'additionalProperties' => $string]],
            RootPageDependentSelect::class => ['type' => 'object', 'additionalProperties' => $string],
            ImageSize::class => ['type' => 'array', 'prefixItems' => [$string, $string, $string], 'items' => false, 'minItems' => 3, 'maxItems' => 3],
            default => null,
        };
    }

    private function getObjectSchema(array $types): array
    {
        $properties = [];

        foreach ($types as $name => $type) {
            $properties[$name] = ['type' => $type, 'default' => 'boolean' === $type ? false : ''];
        }

        return ['type' => 'object', 'properties' => $properties, 'additionalProperties' => false];
    }

    private function convertStructure(mixed $value, array $schema, bool $form): mixed
    {
        if ('object' === ($schema['type'] ?? null)) {
            $values = (array) $value;
            $converted = [];

            foreach ($schema['properties'] ?? [] as $key => $property) {
                $values[$key] ??= $property['default'] ?? '';
            }

            foreach ($values as $key => $item) {
                $property = $schema['properties'][$key] ?? $schema['additionalProperties'] ?? [];
                $converted[$key] = $this->convertStructure($item, \is_array($property) ? $property : [], $form);
            }

            // Preserve JSON objects, including empty maps and maps with numeric keys
            return $form ? $converted : (object) $converted;
        }

        if ('array' === ($schema['type'] ?? null)) {
            $values = array_values((array) $value);

            foreach ($schema['prefixItems'] ?? [] as $key => $itemSchema) {
                $values[$key] ??= $itemSchema['default'] ?? '';
            }

            foreach ($values as $key => $item) {
                $values[$key] = $this->convertStructure($item, $schema['prefixItems'][$key] ?? $schema['items'], $form);
            }

            return $values;
        }

        if ($form) {
            return null === $value || false === $value ? '' : (string) $value;
        }

        return match ($schema['type'] ?? null) {
            'boolean' => (bool) $value,
            'integer' => (int) $value,
            'number' => (float) $value,
            default => (string) $value,
        };
    }

    private function getFileTreeSchema(array $schema, bool $multiple): array
    {
        // The storage length describes binary UUIDs, not their API string representation
        unset($schema['maxLength']);

        return array_replace($schema, $multiple
            ? ['type' => 'array', 'items' => ['type' => 'string', 'format' => 'uuid']]
            : ['type' => ['string', 'null'], 'format' => 'uuid'],
        );
    }

    private function getBooleanSchema(array $schema): array
    {
        unset($schema['maxLength'], $schema['minLength']);
        $schema['type'] = 'boolean';

        return $schema;
    }

    private function convertFileReference(mixed $value, bool $binary): mixed
    {
        return match (true) {
            '' === $value => null,
            $binary && \is_string($value) && 16 === \strlen($value) => StringUtil::binToUuid($value),
            default => $value,
        };
    }

    private function getWidgetType(array $config): string|null
    {
        $widget = $GLOBALS['BE_FFL'][$config['inputType'] ?? ''] ?? null;

        if (!\is_string($widget)) {
            return null;
        }

        foreach (self::WIDGET_TYPES as $type) {
            if (is_a($widget, $type, true)) {
                return $type;
            }
        }

        return null;
    }

    private function decodeArray(mixed $value, array $config): array
    {
        if (null === $value || '' === $value) {
            return [];
        }

        if (!\is_string($value)) {
            return $value;
        }

        return match (true) {
            'json' === ($config['sql']['type'] ?? null) => json_decode($value, true, 512, JSON_THROW_ON_ERROR),
            isset($config['eval']['csv']) => StringUtil::trimsplit($config['eval']['csv'], $value),
            default => StringUtil::deserialize($value, true),
        };
    }
}
