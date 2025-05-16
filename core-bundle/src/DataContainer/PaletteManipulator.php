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

class PaletteManipulator
{
    final public const POSITION_BEFORE = Palette::POSITION_BEFORE;

    final public const POSITION_AFTER = Palette::POSITION_AFTER;

    final public const POSITION_PREPEND = Palette::POSITION_PREPEND;

    final public const POSITION_APPEND = Palette::POSITION_APPEND;

    /**
     * @var list<array{
     *     name: string,
     *     parents: array,
     *     position: string,
     *     hide: bool
     * }>
     */
    private array $legends = [];

    /**
     * @var list<array{
     *     fields: list<string>,
     *     parents: list<string>,
     *     position: string,
     *     fallback: \Closure|list<string>|string|null,
     *     fallbackPosition: string
     * }>
     */
    private array $fields = [];

    /**
     * @var list<array{
     *     fields: list<string>,
     *     legend: string|null
     * }>
     */
    private array $removes = [];

    public static function create(): self
    {
        return new self();
    }

    /**
     * If the legend already exists, nothing will be changed.
     */
    public function addLegend(string $name, array|string|null $parent = null, string $position = self::POSITION_AFTER, bool $hide = false): self
    {
        $this->legends[] = [
            'name' => $name,
            'parents' => (array) $parent,
            'position' => $position,
            'hide' => $hide,
        ];

        return $this;
    }

    /**
     * If $position is PREPEND or APPEND, pass a legend as parent; otherwise pass a
     * field name.
     */
    public function addField(array|string $name, array|string|null $parent = null, string $position = self::POSITION_AFTER, \Closure|array|string|null $fallback = null, string $fallbackPosition = self::POSITION_APPEND): self
    {
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
            'legend' => $legend,
        ];

        return $this;
    }

    /**
     * @throws PaletteNotFoundException
     * @throws PalettePositionException
     */
    public function applyToPalette(string $name, string $table): self
    {
        $palettes = &$GLOBALS['TL_DCA'][$table]['palettes'];

        if (!isset($palettes[$name])) {
            throw new PaletteNotFoundException(\sprintf('Palette "%s" not found in table "%s"', $name, $table));
        }

        $palettes[$name] = $this->applyToString($palettes[$name]);

        return $this;
    }

    /**
     * @throws PaletteNotFoundException
     * @throws PalettePositionException
     */
    public function applyToSubpalette(string $name, string $table): self
    {
        $subpalettes = &$GLOBALS['TL_DCA'][$table]['subpalettes'];

        if (!isset($subpalettes[$name])) {
            throw new PaletteNotFoundException(\sprintf('Subpalette "%s" not found in table "%s"', $name, $table));
        }

        $subpalettes[$name] = $this->applyToString($subpalettes[$name], true);

        return $this;
    }

    /**
     * @throws PalettePositionException
     */
    public function applyToString(Palette|string $palette, bool $skipLegends = false): string
    {
        $palette = $palette instanceof Palette ? $palette : new Palette($palette);

        if (!$skipLegends) {
            foreach ($this->legends as $legend) {
                $palette->addLegend($legend['name'], $legend['parents'], $legend['position'], $legend['hide']);
            }
        }

        foreach ($this->fields as $field) {
            $palette->addField($field['fields'], $field['parents'], $field['position'], $field['fallback'], $field['fallbackPosition'], $skipLegends);
        }

        foreach ($this->removes as $remove) {
            $palette->removeField($remove['fields'], $remove['legend']);
        }

        return (string) $palette;
    }
}
