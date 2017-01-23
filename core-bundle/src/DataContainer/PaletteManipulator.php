<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\DataContainer;

use Contao\CoreBundle\Exception\PaletteNotFoundException;
use Contao\CoreBundle\Exception\PalettePositionException;
use Contao\StringUtil;

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
     * @return static
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
     * @param string       $name
     * @param string|array $parent
     * @param string       $position
     * @param bool         $hide
     *
     * @return static
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
     * @param string|array               $name
     * @param string|array               $parent
     * @param string                     $position
     * @param string|array|\Closure|null $fallback
     * @param string                     $fallbackPosition
     *
     * @return static
     *
     * @throws PalettePositionException
     */
    public function addField($name, $parent, $position = self::POSITION_AFTER, $fallback = null, $fallbackPosition = self::POSITION_APPEND)
    {
        $this->validatePosition($position);

        if (self::POSITION_BEFORE === $fallbackPosition || self::POSITION_AFTER === $fallbackPosition) {
            throw new PalettePositionException('Fallback legend position can only be PREPEND or APPEND');
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
     * @param string $name
     * @param string $table
     *
     * @return static
     */
    public function applyToPalette($name, $table)
    {
        $palettes = &$GLOBALS['TL_DCA'][$table]['palettes'];

        if (!isset($palettes[$name])) {
            throw new PaletteNotFoundException(sprintf('Palette "%s" not found in table "%s"', $name, $table));
        }

        $palettes[$name] = $this->applyToString($palettes[$name]);

        return $this;
    }

    /**
     * Applies the changes to a subpalette.
     *
     * @param string $name
     * @param string $table
     *
     * @return static
     */
    public function applyToSubpalette($name, $table)
    {
        $subpalettes = &$GLOBALS['TL_DCA'][$table]['subpalettes'];

        if (!isset($subpalettes[$name])) {
            throw new PaletteNotFoundException(sprintf('Subpalette "%s" not found in table "%s"', $name, $table));
        }

        $subpalettes[$name] = $this->applyToString($subpalettes[$name], true);

        return $this;
    }

    /**
     * Applies the changes to a palette string.
     *
     * @param string $palette
     * @param bool   $skipLegends
     *
     * @return string
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
     * @param string $position
     *
     * @throws PalettePositionException
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
            throw new PalettePositionException('Invalid legend position');
        }
    }

    /**
     * Converts a palette string to a configuration array.
     *
     * @param string $palette
     *
     * @return array
     */
    private function explode($palette)
    {
        if ('' === (string) $palette) {
            return [];
        }

        $legendCount = 0;
        $legendMap = [];
        $groups = StringUtil::trimsplit(';', $palette);

        foreach ($groups as $group) {
            $hide = false;
            $fields = StringUtil::trimsplit(',', $group);

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
     * @param array $config
     *
     * @return string
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
     * @param array $config
     * @param array $action
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
     * @param array $config
     * @param array $action
     * @param bool  $skipLegends
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
     * @param array $config
     * @param array $action
     * @param bool  $skipLegends
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
     * @param array $config
     * @param array $action
     * @param bool  $skipLegends
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
     * @param array $config
     * @param array $action
     * @param bool  $skipLegends
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
     * @param array $config
     * @param array $action
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
     * @param array  $config
     * @param string $field
     *
     * @return string|false
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
     * @param array  $config
     * @param array  $action
     * @param string $key
     * @param string $position
     *
     * @return bool
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
