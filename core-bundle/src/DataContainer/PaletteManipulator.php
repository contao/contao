<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\DataContainer;

/**
 * Adds fields and legends to DCA palettes.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class PaletteManipulator
{
    const POSITION_BEFORE = 'before';
    const POSITION_AFTER = 'after';
    const POSITION_PREPEND = 'prepend';
    const POSITION_APPEND = 'append';

    /**
     * @var array
     */
    private $legends = [];

    /**
     * @var array
     */
    private $fields = [];

    /**
     * Creates a new object instance.
     *
     * @return static The object instance
     */
    public static function create()
    {
        return new static();
    }

    /**
     * Adds a new legend.
     *
     * If the legend already exists, nothing will be changed.
     *
     * @param string       $name     The name of the new legend
     * @param string|array $parent   The parent legend(s) (first match wins)
     * @param string       $position The position of the new legend
     * @param bool         $hide     True to collapse the palette by default
     *
     * @return static The object instance
     */
    public function addLegend($name, $parent, $position = self::POSITION_AFTER, $hide = false)
    {
        $this->validatePosition($position);

        $this->legends[] = [
            'name' => $name,
            'parents' => (array) $parent,
            'position' => $position,
            'hide' => (bool) $hide,
        ];

        return $this;
    }

    /**
     * Adds a new field.
     *
     * If $position is PREPEND or APPEND, pass a legend as parent; otherwise pass a field name.
     *
     * @param string|array               $name             The name or names of the new field(s)
     * @param string|array               $parent           The parent legend or legends (first match wins)
     * @param string                     $position         The position of the new field(s)
     * @param string|array|\Closure|null $fallback         The fallback palette(s) or a callback
     * @param string                     $fallbackPosition The fallback position (PREPEND or APPEND to legend)
     *
     * @return static The object instance
     *
     * @throws \InvalidArgumentException If $position or $fallbackPosition is invalid
     */
    public function addField(
        $name,
        $parent,
        $position = self::POSITION_AFTER,
        $fallback = null,
        $fallbackPosition = self::POSITION_APPEND
    ) {
        $this->validatePosition($position);

        if (self::POSITION_BEFORE === $fallbackPosition || self::POSITION_AFTER === $fallbackPosition) {
            throw new \InvalidArgumentException('Fallback legend position can only be PREPEND or APPEND');
        }

        $this->fields[] = [
            'fields' => (array) $name,
            'parents' => (array) $parent,
            'position' => $position,
            'fallback' => is_scalar($fallback) ? [$fallback] : $fallback,
            'fallbackPosition' => $fallbackPosition,
        ];

        return $this;
    }

    /**
     * Applies the changes to a palette.
     *
     * @param string $name  The palette name
     * @param string $table The DCA table name
     *
     * @return static The object instance
     */
    public function applyToPalette($name, $table)
    {
        $palettes = &$GLOBALS['TL_DCA'][$table]['palettes'];

        if (!isset($palettes[$name])) {
            throw new \InvalidArgumentException(sprintf('Palette "%s" not found in table "%s"', $name, $table));
        }

        $palettes[$name] = $this->applyToString($palettes[$name]);

        return $this;
    }

    /**
     * Applies the changes to a subpalette.
     *
     * @param string $name  The subpalette name
     * @param string $table The DCA table name
     *
     * @return static The object instance
     */
    public function applyToSubpalette($name, $table)
    {
        $subpalettes = &$GLOBALS['TL_DCA'][$table]['subpalettes'];

        if (!isset($subpalettes[$name])) {
            throw new \InvalidArgumentException(sprintf('Subpalette "%s" not found in table "%s"', $name, $table));
        }

        $subpalettes[$name] = $this->applyToString($subpalettes[$name], true);

        return $this;
    }

    /**
     * Applies the changes to a palette string.
     *
     * @param string $palette     The palette or subpalette string
     * @param bool   $skipLegends True to ignore legends (e.g. for subpalettes)
     *
     * @return string The palette string
     */
    public function applyToString($palette, $skipLegends = false)
    {
        $config = $this->explode($palette);

        if (!$skipLegends) {
            foreach ($this->legends as $legend) {
                $this->applyLegend($config, $legend);
            }
        }

        // Make sure there is at least one legend
        if (0 === count($config)) {
            $config = [['fields' => [], 'hide' => false]];
        }

        foreach ($this->fields as $field) {
            $this->applyField($config, $field, $skipLegends);
        }

        return $this->implode($config);
    }

    /**
     * Validates the position.
     *
     * @param string $position The position
     *
     * @throws \InvalidArgumentException If the position is not valid
     */
    private function validatePosition($position)
    {
        static $positions = [
            self::POSITION_BEFORE,
            self::POSITION_AFTER,
            self::POSITION_PREPEND,
            self::POSITION_APPEND,
        ];

        if (!in_array($position, $positions, true)) {
            throw new \InvalidArgumentException('Invalid legend position');
        }
    }

    /**
     * Converts a palette string to a configuration array.
     *
     * @param string $palette The palette string
     *
     * @return array The configuration array
     */
    private function explode($palette)
    {
        if ('' === (string) $palette) {
            return [];
        }

        $legendCount = 0;
        $legendMap = [];

        foreach (array_map('trim', explode(';', $palette)) as $group) {
            $hide = false;
            $fields = array_map('trim', explode(',', $group));

            if (preg_match('#\{(.+?)(:hide)?\}#', $fields[0], $matches)) {
                $legend = $matches[1];
                $hide = count($matches) > 2 && ':hide' === $matches[2];
                array_shift($fields);
            } else {
                $legend = $legendCount++;
            }

            $legendMap[$legend] = [
                'fields' => $fields,
                'hide' => $hide,
            ];
        }

        return $legendMap;
    }

    /**
     * Converts a configuration array to a palette string.
     *
     * @param array $config The configuration array
     *
     * @return string The palette string
     */
    private function implode(array $config)
    {
        $palette = '';

        foreach ($config as $legend => $group) {
            if (count($group['fields']) < 1) {
                continue;
            }

            if ('' !== $palette) {
                $palette .= ';';
            }

            if (!is_int($legend)) {
                $palette .= sprintf('{%s%s},', $legend, ($group['hide'] ? ':hide' : ''));
            }

            $palette .= implode(',', $group['fields']);
        }

        return $palette;
    }

    /**
     * Adds a new legend to the configuration array.
     *
     * @param array $config The configuration array
     * @param array $action The action array
     */
    private function applyLegend(array &$config, array $action)
    {
        // Legend already exists, do nothing
        if (array_key_exists($action['name'], $config)) {
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
            if (array_key_exists($parent, $config)) {
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

    /**
     * Adds a new field to the configuration array.
     *
     * @param array $config      The configuration array
     * @param array $action      The action array
     * @param bool  $skipLegends True to ignore legends (e.g. for subpalettes)
     */
    private function applyField(array &$config, array $action, $skipLegends = false)
    {
        if (self::POSITION_PREPEND === $action['position'] || self::POSITION_APPEND === $action['position']) {
            $this->applyFieldToLegend($config, $action, $skipLegends);
        } else {
            $this->applyFieldToField($config, $action, $skipLegends);
        }
    }

    /**
     * Adds fields to a legend.
     *
     * @param array $config      The configuration array
     * @param array $action      The action array
     * @param bool  $skipLegends True to ignore legends (e.g. for subpalettes)
     */
    private function applyFieldToLegend(array &$config, array $action, $skipLegends = false)
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

    /**
     * Adds a field after a field.
     *
     * @param array $config      The configuration array
     * @param array $action      The action array
     * @param bool  $skipLegends True to ignore legends (e.g. for subpalettes)
     */
    private function applyFieldToField(array &$config, array $action, $skipLegends = false)
    {
        $offset = (int) (self::POSITION_AFTER === $action['position']);

        foreach ($action['parents'] as $parent) {
            $legend = $this->findLegendForField($config, $parent);

            if (false !== $legend) {
                $legend = (string) $legend;
                $offset += array_search($parent, $config[$legend]['fields'], true);
                array_splice($config[$legend]['fields'], $offset, 0, $action['fields']);

                return;
            }
        }

        $this->applyFallback($config, $action, $skipLegends);
    }

    /**
     * Applies the fallback when adding a field fails.
     *
     * Adds a new legend if possible or appends to the last one.
     *
     * @param array $config      The configuration array
     * @param array $action      The action array
     * @param bool  $skipLegends True to ignore legends (e.g. for subpalettes)
     */
    private function applyFallback(array &$config, array $action, $skipLegends = false)
    {
        if (is_callable($action['fallback'])) {
            $action['fallback']($config, $action, $skipLegends);
        } else {
            $this->applyFallbackPalette($config, $action);
        }
    }

    /**
     * Aplies the fallback to a palette.
     *
     * @param array $config The configuration array
     * @param array $action Th eaction array
     */
    private function applyFallbackPalette(array &$config, array $action)
    {
        end($config);
        $fallback = key($config);

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
        $offset = self::POSITION_PREPEND === $action['fallbackPosition'] ? 0 : count($config[$fallback]['fields']);
        array_splice($config[$fallback]['fields'], $offset, 0, $action['fields']);
    }

    /**
     * Searches all legends for a field.
     *
     * Having the same field in multiple legends is not supported by Contao, so we don't handle that case.
     *
     * @param array  $config The configuration array
     * @param string $field  The field name
     *
     * @return string|false The legend or false
     */
    private function findLegendForField(array &$config, $field)
    {
        foreach ($config as $legend => $group) {
            if (in_array($field, $group['fields'], true)) {
                return $legend;
            }
        }

        return false;
    }

    /**
     * Tries to apply to a parent.
     *
     * @param array  $config   The configuration array
     * @param array  $action   The action array
     * @param string $key      The action key
     * @param string $position The position key
     *
     * @return bool True if the operation was successful
     */
    private function canApplyToParent(array &$config, array $action, $key, $position)
    {
        foreach ($action[$key] as $parent) {
            if (array_key_exists($parent, $config)) {
                $offset = self::POSITION_PREPEND === $action[$position] ? 0 : count($config[$parent]['fields']);
                array_splice($config[$parent]['fields'], $offset, 0, $action['fields']);

                return true;
            }
        }

        return false;
    }
}
