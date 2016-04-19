<?php

/*
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\DataContainer;

/**
 * PaletteManipulator is a tool to add fields and legends to DCA palettes.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class PaletteManipulator
{
    const POSITION_PREPEND = 'prepend';
    const POSITION_APPEND  = 'append';
    const POSITION_BEFORE  = 'before';
    const POSITION_AFTER   = 'after';

    /**
     * Legends to be added to the palette.
     * @var array
     */
    private $legends = [];

    /**
     * Fields to be added to the palette.
     * @var array
     */
    private $fields  = [];

    /**
     * Adds a new legend into the palette.
     * If the legend already exists, nothing will be changed on the palette.
     *
     * @param string       $name     Name of the new legend.
     * @param string|array $parent   The name of the relative legend in regards to position.
     *                               If value is an array, each legend will be tried, first wins.
     * @param string       $position Where to place the new legend.
     * @param bool         $hide     If the new legend should be collapsed by default.
     *
     * @return $this
     *
     * @throws \InvalidArgumentException If $position or $fallbackPosition is invalid.
     */
    public function addLegend($name, $parent, $position = self::POSITION_AFTER, $hide = false)
    {
        $this->validatePosition($position);

        $this->legends[] = [
            'name'     => $name,
            'parents'  => (array) $parent,
            'position' => $position,
            'hide'     => (bool) $hide
        ];

        return $this;
    }

    /**
     * Adds a new field to the palette.
     * If position is PREPEND or APPEND, pass a legend as parent. Otherwise pass a field name.
     *
     * @param string|array          $name             Name of the new field. Can be an array to add multiple fields.
     * @param string|array          $parent           The name of the relative field or legend in regards to position.
     *                                                If value is an array, each field/legend will be tried, first wins.
     * @param string                $position         Where to place the new field(s)
     * @param string|array|\Closure $fallback         Name or list of fallback palettes if none of the fields is found.
     *                                                Can also be a Closure to be executed when unsuccessful.
     * @param string                $fallbackPosition Position to use for fallback (PREPEND or APPEND to legend).
     *
     * @return $this
     *
     * @throws \InvalidArgumentException If $position or $fallbackPosition is invalid.
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
            'fields'           => (array) $name,
            'parents'          => (array) $parent,
            'position'         => $position,
            'fallback'         => is_scalar($fallback) ? [$fallback] : $fallback,
            'fallbackPosition' => $fallbackPosition,
        ];

        return $this;
    }

    /**
     * Apply changes to a palette on a DCA table.
     *
     * @param string $name  The palette name.
     * @param string $table The DCA table name.
     *
     * @return $this
     *
     * @throws \UnderflowException If the DCA for given table is not loaded or palette does not exist.
     */
    public function applyToPalette($name, $table)
    {
        if (!isset($GLOBALS['TL_DCA'][$table]['palettes'][$name])) {
            throw new \UnderflowException(sprintf('Palette "%s" not found in table "%s"', $name, $table));
        }

        $GLOBALS['TL_DCA'][$table]['palettes'][$name] = $this->apply($GLOBALS['TL_DCA'][$table]['palettes'][$name]);

        return $this;
    }

    /**
     * Apply changes to a subpalette on a DCA table.
     *
     * @param string $name  The subpalette name.
     * @param string $table The DCA table name.
     *
     * @return $this
     *
     * @throws \UnderflowException If the DCA for given table is not loaded or subpalette does not exist.
     */
    public function applyToSubpalette($name, $table)
    {
        if (!isset($GLOBALS['TL_DCA'][$table]['subpalettes'][$name])) {
            throw new \UnderflowException(sprintf('Subpalette "%s" not found in table "%s"', $name, $table));
        }

        $GLOBALS['TL_DCA'][$table]['subpalettes'][$name] = $this->apply(
            $GLOBALS['TL_DCA'][$table]['subpalettes'][$name],
            true
        );

        return $this;
    }

    /**
     * Apply changes to the given palette string.
     *
     * @param string $palette     A palette or subpalette string.
     * @param bool   $skipLegends If to ignore legends (e.g. for subpalettes).
     *
     * @return string
     */
    public function applyToString($palette, $skipLegends = false)
    {
        return $this->apply($palette, $skipLegends);
    }

    /**
     * Validates the positions value is known.
     *
     * @param string $position
     *
     * @throws \InvalidArgumentException If the position is not valid.
     */
    private function validatePosition($position)
    {
        static $positions = [
            self::POSITION_PREPEND,
            self::POSITION_APPEND,
            self::POSITION_BEFORE,
            self::POSITION_AFTER,
        ];

        if (!in_array($position, $positions, true)) {
            throw new \InvalidArgumentException('Legend position must be one of the PaletteManipulator constants.');
        }
    }

    /**
     * Converts palette string to a configuration array.
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
        $legendMap   = [];

        foreach (array_map('trim', explode(';', $palette)) as $group) {
            $legend = null;
            $hide   = false;
            $fields = array_map('trim', explode(',', $group));

            if (preg_match('#\{(.+?)(:hide)?\}#', $fields[0], $matches)) {
                $legend = $matches[1];
                $hide   = count($matches) > 2 && ':hide' === $matches[2];
                array_shift($fields);
            } else {
                $legend = $legendCount++;
            }

            $legendMap[$legend] = [
                'fields' => $fields,
                'hide'   => $hide,
            ];
        }

        return $legendMap;
    }

    /**
     * Converts configuration array to a palette string.
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
                $palette .= '{' . $legend . ($group['hide'] ? ':hide' : '') . '},';
            }

            $palette .= implode(',', $group['fields']);
        }

        return $palette;
    }

    /**
     * Apply all changes to the given palette.
     *
     * @param string $palette
     * @param bool   $skipLegends
     *
     * @return string
     */
    private function apply($palette, $skipLegends = false)
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
            /** @noinspection AdditionOperationOnArraysInspection */
            $config = $template + $config;
            return;
        }

        if (self::POSITION_APPEND === $action['position']) {
            $config += $template;
            return;
        }

        foreach ($action['parents'] as $parent) {
            if (array_key_exists($parent, $config)) {
                $offset  = array_search($parent, array_keys($config), true);
                $offset += (int) (self::POSITION_AFTER === $action['position']);

                // Necessary because array_splice() would remove keys from $replacement array
                $before = array_splice($config, 0, $offset);
                $config = $before + $template + $config;
                return;
            }
        }

        // If everything fails, append new legend at the end
        $config += $template;
    }

    /**
     * Adds a new field to the configuration array.
     *
     * @param array $config
     * @param array $action
     * @param bool  $skipLegends
     *
     * @return bool
     */
    private function applyField(array &$config, array $action, $skipLegends = false)
    {
        if ($action['position'] === self::POSITION_PREPEND || $action['position'] === self::POSITION_APPEND) {
            return $this->applyFieldToLegend($config, $action, $skipLegends);
        }

        return $this->applyFieldToField($config, $action, $skipLegends);
    }

    /**
     * Adds fields to a legend.
     *
     * @param array $config
     * @param array $action
     * @param bool  $skipLegends
     *
     * @return bool
     */
    private function applyFieldToLegend(array &$config, array $action, $skipLegends = false)
    {
        // If $skipLegends is true, we usually only have one legend without name, so we simply append to that.
        if ($skipLegends) {
            if (self::POSITION_PREPEND === $action['position']) {
                reset($config);
            } else {
                end($config);
            }

            $action['parents'] = [key($config)];
        }

        foreach ($action['parents'] as $parent) {
            if (array_key_exists($parent, $config)) {
                $offset = self::POSITION_PREPEND === $action['position'] ? 0 : count($config[$parent]['fields']);
                array_splice($config[$parent]['fields'], $offset, 0, $action['fields']);

                return true;
            }
        }

        return $this->applyFallback($config, $action, $skipLegends);
    }

    /**
     * Adds a field after a field.
     *
     * @param array $config
     * @param array $action
     * @param bool  $skipLegends
     *
     * @return bool
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

                return true;
            }
        }

        return $this->applyFallback($config, $action, $skipLegends);
    }

    /**
     * Handles fallback when adding a field fails. Adds a new legend if possible or append to the last available one.
     *
     * @param array $config
     * @param array $action
     * @param bool  $skipLegends
     *
     * @return bool
     */
    private function applyFallback(array &$config, array $action, $skipLegends = false)
    {
        // Call fallback closure if none of the parents was found.
        if ($action['fallback'] instanceof \Closure) {
            return $action['fallback']($config, $action, $skipLegends);
        }

        end($config);
        $fallback = key($config);

        if (null !== $action['fallback']) {
            foreach ($action['fallback'] as $parent) {
                if (array_key_exists($parent, $config)) {
                    $offset = self::POSITION_PREPEND === $action['fallbackPosition'] ? 0 : count($config[$parent]['fields']);
                    array_splice($config[$parent]['fields'], $offset, 0, $action['fields']);

                    return true;
                }
            }

            // If the fallback palette was not found, create a new one.
            $fallback = reset($action['fallback']);
            $this->applyLegend(
                $config,
                [
                    'name'     => $fallback,
                    'position' => self::POSITION_APPEND,
                    'hide'     => false
                ]
            );
        }

        // If everything fails, add to the last legend
        $offset = self::POSITION_PREPEND === $action['fallbackPosition'] ? 0 : count($config[$fallback]['fields']);
        array_splice($config[$fallback]['fields'], $offset, 0, $action['fields']);

        return true;
    }

    /**
     * Search all legends for a field.
     * Having the same field in multiple legends is not supported by Contao, so we don't handle that case.
     *
     * @param array  $config
     * @param string $field
     *
     * @return string|bool
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
     * Creates a new instance of PaletteManipulator.
     *
     * @return static
     */
    public static function create()
    {
        return new static();
    }
}
