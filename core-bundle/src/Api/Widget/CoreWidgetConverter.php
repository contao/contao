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
use Contao\CoreBundle\Widget\DateValueFormatter;
use Contao\FileTree;
use Contao\PageTree;
use Contao\Password;
use Contao\RadioButton;
use Contao\SelectMenu;
use Contao\StringUtil;
use Contao\TextArea;
use Contao\TextField;

final class CoreWidgetConverter implements WidgetConverterInterface
{
    public function __construct(private readonly DateValueFormatter $dateValueFormatter)
    {
    }

    public function supports(array $config): bool
    {
        return null !== $this->getWidgetType($config);
    }

    public function getSchema(array $config, array $schema): array
    {
        $multiple = $config['eval']['multiple'] ?? false;

        return match ($this->getWidgetType($config)) {
            FileTree::class => $this->getFileTreeSchema($schema, $multiple),
            PageTree::class => array_replace($schema, $multiple ? ['type' => 'array', 'items' => ['type' => 'integer']] : ['type' => 'integer']),
            CheckBox::class => $multiple ? $schema : $this->getBooleanSchema($schema),
            Password::class => array_replace($schema, ['writeOnly' => true]),
            default => $schema,
        };
    }

    public function convertToApiValue(mixed $value, array $config, array $schema): mixed
    {
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
        if (null === $value || false === $value) {
            return '';
        }

        if (\is_array($value)) {
            $values = array_map(fn ($item) => $this->convertToFormValue($item, $config, $schema['items'] ?? []), $value);

            return match ($this->getWidgetType($config)) {
                FileTree::class, PageTree::class => implode(',', $values),
                default => $values,
            };
        }

        return $this->dateValueFormatter->format($value, $config['eval']['rgxp'] ?? '') ?? (string) $value;
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

        foreach ([FileTree::class, PageTree::class, CheckBox::class, Password::class, TextField::class, TextArea::class, SelectMenu::class, RadioButton::class] as $type) {
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
