<?php

/*
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\DataContainer;

class PaletteManipulator
{
    const POSITION_PREPEND       = 'prepend';
    const POSITION_APPEND        = 'append';
    const POSITION_BEFORE_FIELD  = 'before_field';
    const POSITION_AFTER_FIELD   = 'after_field';
    const POSITION_BEFORE_LEGEND = 'before_legend';
    const POSITION_AFTER_LEGEND  = 'after_legend';

    /**
     * @var string
     */
    private $position;

    /**
     * @var array
     */
    private $new;

    /**
     * @var string
     */
    private $target;

    /**
     * @var PaletteManipulator
     */
    private $fallback;

    /**
     * Constructor.
     *
     * @param string $position
     * @param array  $new
     * @param string $target
     */
    public function __construct($position, array $new, $target)
    {
        $this->position = $position;
        $this->new      = $new;
        $this->target   = $target;
    }

    /**
     * @param PaletteManipulator $fallback
     *
     * @return $this
     */
    public function setFallback(PaletteManipulator $fallback)
    {
        $this->fallback = $fallback;

        return $this;
    }


    public function applyTo($palette)
    {
        $config = $this->explode($palette);

        switch ($this->position) {
            case self::POSITION_PREPEND:
                $result = $this->addFields($config, $palette, $this->target, 0);
                break;

            case self::POSITION_APPEND:
                $result = $this->addFields($config, $palette, $this->target, count($config));
                break;

            case self::POSITION_BEFORE_FIELD:
                $result = $this->addFields($config, $palette, $this->findPaletteForField($config, $this->target), 0, $this->target);
                break;

            case self::POSITION_AFTER_FIELD:
                $result = $this->addFields($config, $palette, $this->findPaletteForField($config, $this->target), 1, $this->target);
                break;

            case self::POSITION_BEFORE_LEGEND:
                $result = $this->addLegend($config, $palette, 0);
                break;

            case self::POSITION_AFTER_LEGEND:
                $result = $this->addLegend($config, $palette, 1);
                break;

            default:
                throw new \InvalidArgumentException(sprintf('Unknown position "%s"', $this->position));
        }

        return false === $result ? $palette : $this->implode($config);
    }

    /**
     * @param string $palette
     *
     * @return array
     */
    private function explode($palette)
    {
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
     * @param array $config
     *
     * @return string
     */
    private function implode(array $config)
    {
        $palette = '';

        foreach ($config as $legend => $group) {
            if ('' !== $palette) {
                $palette .= ';';
            }

            if (!is_int($legend)) {
                $palette .= '{' . $legend . (isset($group['hide']) && $group['hide'] ? ':hide' : '') . '},';
            }

            $palette .= implode(',', $group['fields']);
        }

        return $palette;
    }

    /**
     * @param array  $config
     * @param string $palette
     * @param string $legend
     * @param int    $offset
     */
    private function addFields(array &$config, &$palette, $legend, $offset, $target = null)
    {
        // If legend does not exist, try to apply fallback or append new legend to the end
        if (false === $legend || !isset($config[$legend])) {
            if ($this->applyFallback($palette)) {
                return false;
            }
            
            $config[$legend] = ['fields' => $this->new];

            return true;
        }

        if (null !== $target) {
            $offset = array_search($this->target, $config[$legend]['fields'], true) + $offset;
        }
        
        array_splice($config[$legend]['fields'], $offset,0, $this->new);
        
        return true;
    }

    /**
     * @param array  $config
     * @param string $palette
     * @param int    $offset
     */
    private function addLegend(array &$config, &$palette, $offset)
    {
        if (!isset($config[$this->target])) {
            if ($this->applyFallback($palette)) {
                return false;
            }

            foreach ($this->new as $legend => $group) {
                $config[$legend] = $group;
            }

            return true;
        }

        $offset = array_search($this->target, array_keys($config), true) + $offset;

        $before = array_splice($config, 0, $offset);

        $config = $before + $this->new + $config;

        return true;
    }

    /**
     * @param array  $config
     * @param string $field
     *
     * @return string|bool
     */
    private function findPaletteForField(array $config, $field)
    {
        foreach ($config as $legend => $group) {
            if (in_array($field, $group['fields'], true)) {
                return $legend;
            }
        }

        return false;
    }

    /**
     * @param string $palette
     *
     * @return bool
     */
    private function applyFallback(&$palette)
    {
        if (null === $this->fallback) {
            return false;
        }

        $palette = $this->fallback->applyTo($palette);

        return true;
    }


    public static function prepend($legend, $new)
    {
        return new static(self::POSITION_PREPEND, (array) $new, $legend);
    }

    public static function append($legend, $new)
    {
        return new static(self::POSITION_APPEND, (array) $new, $legend);
    }

    public static function beforeField($field, $new)
    {
        return new static(self::POSITION_BEFORE_FIELD, (array) $new, $field);
    }

    public static function afterField($field, $new)
    {
        return new static(self::POSITION_AFTER_FIELD, (array) $new, $field);
    }

    public static function beforeLegend($beforeLegend, $name, $new, $hide = false)
    {
        return new static(
            self::POSITION_BEFORE_LEGEND,
            [
                $name => [
                    'fields' => (array) $new,
                    'hide'   => $hide
                ]
            ],
            $beforeLegend,
            null
        );
    }

    public static function afterLegend($afterLegend, $name, $new, $hide = false)
    {
        return new static(
            self::POSITION_AFTER_LEGEND,
            [
                $name => [
                    'fields' => (array) $new,
                    'hide'   => $hide
                ]
            ],
            $afterLegend,
            null
        );
    }
}
