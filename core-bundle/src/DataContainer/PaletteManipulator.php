<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\DataContainer;

use Contao\StringUtil;

class PaletteManipulator
{
    final public const POSITION_BEFORE = 'before';
    final public const POSITION_AFTER = 'after';
    final public const POSITION_PREPEND = 'prepend';
    final public const POSITION_APPEND = 'append';

    private array $legends = [];
    private array $fields = [];
    private array $removes = [];

    public static function create(): self
    {
        return new self();
    }

    /**
     * If the legend already exists, nothing will be changed.
     *
     * @throws PalettePositionException
     */
    public function addLegend(string $name, array|string|null $parent = null, string $position = self::POSITION_AFTER, bool $hide = false): self
    {
        $this->validatePosition($position);

        $this->legends[] = [
            'name' => $name,
            'parents' => (array) $parent,
            'position' => $position,
            'hide' => $hide,
        ];

        return $this;
    }

    /**
     * If $position is PREPEND or APPEND, pass a legend as parent; otherwise pass a field name.
     *
     * @throws PalettePositionException
     */
    public function addField(array|string $name, array|string|null $parent = null, string $position = self::POSITION_AFTER, \Closure|array|string|null $fallback = null, string $fallbackPosition = self::POSITION_APPEND): self
    {
        $this->validatePosition($position);

        if (self::POSITION_BEFORE === $fallbackPosition || self::POSITION_AFTER === $fallbackPosition) {
            throw new PalettePositionException('Fallback legend position can only be PREPEND or APPEND');
        }

        $this->fields[] = [
            'fields' => (array) $name,
            'parents' => (array) $parent,
            'position' => $position,
            'fallback' => \is_scalar($fallback) ? [$fallback] : $fallback,
            'fallbackPosition' => $fallbackPosition,
        ];

        return $this;
    }

    /**
     * If no legend is given, the field is removed everywhere.
     */
    public function removeField(array|string $name, string|null $legend = null): self
    {
        $this->removes[] = [
            'fields' => (array) $name,
            'parents' => (array) $legend,
        ];

        return $this;
    }

    public function applyToPalette(string $name, string $table): self
    {
        $palettes = &$GLOBALS['TL_DCA'][$table]['palettes'];

        if (!isset($palettes[$name])) {
            throw new PaletteNotFoundException(sprintf('Palette "%s" not found in table "%s"', $name, $table));
        }

        $palettes[$name] = $this->applyToString($palettes[$name]);

        return $this;
    }

    public function applyToSubpalette(string $name, string $table): self
    {
        $subpalettes = &$GLOBALS['TL_DCA'][$table]['subpalettes'];

        if (!isset($subpalettes[$name])) {
            throw new PaletteNotFoundException(sprintf('Subpalette "%s" not found in table "%s"', $name, $table));
        }

        $subpalettes[$name] = $this->applyToString($subpalettes[$name], true);

        return $this;
    }

    public function applyToString(string $palette, bool $skipLegends = false): string
    {
        $config = $this->explode($palette);

        if (!$skipLegends) {
            foreach ($this->legends as $legend) {
                $this->applyLegend($config, $legend);
            }
        }

        // Make sure there is at least one legend
        if (0 === \count($config)) {
            $config = [['fields' => [], 'hide' => false]];
        }

        foreach ($this->fields as $field) {
            $this->applyField($config, $field, $skipLegends);
        }

        foreach ($this->removes as $remove) {
            $this->applyRemove($config, $remove);
        }

        return $this->implode($config);
    }

    /**
     * @throws PalettePositionException
     */
    private function validatePosition(string $position): void
    {
        static $positions = [
            self::POSITION_BEFORE,
            self::POSITION_AFTER,
            self::POSITION_PREPEND,
            self::POSITION_APPEND,
        ];

        if (!\in_array($position, $positions, true)) {
            throw new PalettePositionException('Invalid legend position');
        }
    }

    /**
     * Converts a palette string to a configuration array.
     *
     * @return array<int|string, array>
     */
    private function explode(string $palette): array
    {
        if ('' === $palette) {
            return [];
        }

        $legendCount = 0;
        $legendMap = [];
        $groups = StringUtil::trimsplit(';', $palette);

        foreach ($groups as $group) {
            if ('' === $group) {
                continue;
            }

            $hide = false;
            $fields = StringUtil::trimsplit(',', $group);

            if (preg_match('#{(.+?)(:hide)?}#', (string) $fields[0], $matches)) {
                $legend = $matches[1];
                $hide = \count($matches) > 2 && ':hide' === $matches[2];
                array_shift($fields);
            } else {
                $legend = $legendCount++;
            }

            $legendMap[$legend] = ['fields' => $fields, 'hide' => $hide];
        }

        return $legendMap;
    }

    /**
     * Converts a configuration array to a palette string.
     */
    private function implode(array $config): string
    {
        $palette = '';

        foreach ($config as $legend => $group) {
            if (\count($group['fields']) < 1) {
                continue;
            }

            if ('' !== $palette) {
                $palette .= ';';
            }

            if (!\is_int($legend)) {
                $palette .= sprintf('{%s%s},', $legend, $group['hide'] ? ':hide' : '');
            }

            $palette .= implode(',', $group['fields']);
        }

        return $palette;
    }

    private function applyLegend(array &$config, array $action): void
    {
        // Legend already exists, do nothing
        if (\array_key_exists($action['name'], $config)) {
            return;
        }

        $template = [$action['name'] => ['fields' => [], 'hide' => $action['hide']]];

        if (self::POSITION_PREPEND === $action['position']) {
            $config = $template + $config;

            return;
        }

        if (self::POSITION_APPEND === $action['position']) {
            $config += $template;

            return;
        }

        foreach ($action['parents'] as $parent) {
            if (\array_key_exists($parent, $config)) {
                $offset = array_search($parent, array_keys($config), true);
                $offset += (int) (self::POSITION_AFTER === $action['position']);

                // Necessary because array_splice() would remove the keys from the replacement array
                $before = array_splice($config, 0, $offset);
                $config = $before + $template + $config;

                return;
            }
        }

        // If everything fails, append the new legend at the end
        $config += $template;
    }

    private function applyField(array &$config, array $action, bool $skipLegends = false): void
    {
        if (self::POSITION_PREPEND === $action['position'] || self::POSITION_APPEND === $action['position']) {
            $this->applyFieldToLegend($config, $action, $skipLegends);
        } else {
            $this->applyFieldToField($config, $action, $skipLegends);
        }
    }

    private function applyFieldToLegend(array &$config, array $action, bool $skipLegends = false): void
    {
        // If $skipLegends is true, we usually only have one legend without name, so we simply append to that
        if ($skipLegends) {
            if (self::POSITION_PREPEND === $action['position']) {
                reset($config);
            } else {
                end($config);
            }

            $action['parents'] = [key($config)];
        }

        if ($this->canApplyToParent($config, $action, 'parents', 'position')) {
            return;
        }

        $this->applyFallback($config, $action, $skipLegends);
    }

    private function applyFieldToField(array &$config, array $action, bool $skipLegends = false): void
    {
        $offset = (int) (self::POSITION_AFTER === $action['position']);

        foreach ($action['parents'] as $parent) {
            $legend = $this->findLegendForField($config, $parent);

            if (false !== $legend) {
                $offset += array_search($parent, $config[$legend]['fields'], true);
                array_splice($config[$legend]['fields'], $offset, 0, $action['fields']);

                return;
            }
        }

        $this->applyFallback($config, $action, $skipLegends);
    }

    /**
     * Adds a new legend if possible or appends to the last one.
     */
    private function applyFallback(array &$config, array $action, bool $skipLegends = false): void
    {
        if (\is_callable($action['fallback'])) {
            $action['fallback']($config, $action, $skipLegends);
        } else {
            $this->applyFallbackPalette($config, $action);
        }
    }

    private function applyFallbackPalette(array &$config, array $action): void
    {
        $fallback = array_key_last($config);

        if (null !== $action['fallback']) {
            if ($this->canApplyToParent($config, $action, 'fallback', 'fallbackPosition')) {
                return;
            }

            // If the fallback palette was not found, create a new one
            $fallback = reset($action['fallback']);

            $this->applyLegend(
                $config,
                [
                    'name' => $fallback,
                    'position' => self::POSITION_APPEND,
                    'hide' => false,
                ]
            );
        }

        // If everything fails, add to the last legend
        $offset = self::POSITION_PREPEND === $action['fallbackPosition'] ? 0 : \count($config[$fallback]['fields']);
        array_splice($config[$fallback]['fields'], $offset, 0, $action['fields']);
    }

    private function applyRemove(array &$config, array $remove): void
    {
        foreach ($config as $legend => $group) {
            if (empty($remove['parents']) || \in_array($legend, $remove['parents'], true)) {
                $config[$legend]['fields'] = array_diff($group['fields'], $remove['fields']);
            }
        }
    }

    /**
     * Having the same field in multiple legends is not supported by Contao, so we don't handle that case.
     */
    private function findLegendForField(array $config, string $field): int|string|false
    {
        foreach ($config as $legend => $group) {
            if (\in_array($field, $group['fields'], true)) {
                return $legend;
            }
        }

        return false;
    }

    private function canApplyToParent(array &$config, array $action, string $key, string $position): bool
    {
        foreach ($action[$key] as $parent) {
            if (\array_key_exists($parent, $config)) {
                $offset = self::POSITION_PREPEND === $action[$position] ? 0 : \count($config[$parent]['fields']);
                array_splice($config[$parent]['fields'], $offset, 0, $action['fields']);

                return true;
            }
        }

        return false;
    }
}
