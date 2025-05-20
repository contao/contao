<?php

declare(strict_types=1);

namespace Contao\CoreBundle\DataContainer;

use Contao\StringUtil;

class Palette implements \Stringable
{
    final public const POSITION_BEFORE = 'before';

    final public const POSITION_AFTER = 'after';

    final public const POSITION_PREPEND = 'prepend';

    final public const POSITION_APPEND = 'append';

    /**
     * @param array<string|int, array{fields: array<string>, hide: bool}> $palette
     */
    private array $config;

    public function __construct(string $palette = '')
    {
        $this->config = $this->explode($palette);
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function toString(): string
    {
        return $this->implode();
    }

    public function hasLegend(string $legend): bool
    {
        return isset($this->config[$legend]);
    }

    /**
     * @param list<string>|string|null $parents Optionally filter for a legend
     */
    public function hasField(string $field, array|string|null $parents = null): bool
    {
        $parents = $parents ? (array) $parents : null;

        foreach ($this->config as $legend => $legendConfig) {
            if (null !== $parents && !\in_array($legend, $parents, true)) {
                continue;
            }

            if (\in_array($field, $legendConfig['fields'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Having the same field in multiple legends is not supported by Contao, so we
     * don't handle that case.
     */
    public function getLegendForField(string $name): int|string|null
    {
        foreach ($this->config as $legend => $group) {
            if (\in_array($name, $group['fields'], true)) {
                return $legend;
            }
        }

        return null;
    }

    /**
     * If the legend already exists, nothing will be changed.
     *
     * @throws PalettePositionException
     */
    public function addLegend(string $name, array|string|null $parents = null, string $position = self::POSITION_AFTER, bool $hide = false): self
    {
        $this->validatePosition($position);

        // Legend already exists, do nothing
        if (\array_key_exists($name, $this->config)) {
            return $this;
        }

        $template = [$name => ['fields' => [], 'hide' => $hide]];

        if (self::POSITION_PREPEND === $position) {
            $this->config = $template + $this->config;

            return $this;
        }

        if (self::POSITION_APPEND === $position) {
            $this->config += $template;

            return $this;
        }

        foreach ((array) $parents as $parent) {
            if (\array_key_exists($parent, $this->config)) {
                $offset = array_search($parent, array_keys($this->config), true);
                $offset += (int) (self::POSITION_AFTER === $position);

                // Necessary because array_splice() would remove the keys from the replacement array
                $before = array_splice($this->config, 0, $offset);
                $this->config = $before + $template + $this->config;

                return $this;
            }
        }

        // If everything fails, append the new legend at the end
        $this->config += $template;

        return $this;
    }

    /**
     * If $position is PREPEND or APPEND, pass a legend as parent; otherwise pass a
     * field name.
     *
     * @throws PalettePositionException
     */
    public function addField(array|string $name, array|string|null $parent = null, string $position = self::POSITION_AFTER, \Closure|array|string|null $fallback = null, string $fallbackPosition = self::POSITION_APPEND, bool $skipLegends = false): self
    {
        $this->validatePosition($position);

        if (self::POSITION_BEFORE === $fallbackPosition || self::POSITION_AFTER === $fallbackPosition) {
            throw new PalettePositionException('Fallback legend position can only be PREPEND or APPEND');
        }

        if (self::POSITION_PREPEND === $position || self::POSITION_APPEND === $position) {
            $this->addFieldsToLegend((array) $name, (array) $parent, $position, $fallback, $fallbackPosition, $skipLegends);
        } else {
            $this->addFieldsToField((array) $name, (array) $parent, $position, $fallback, $fallbackPosition, $skipLegends);
        }

        return $this;
    }

    /**
     * If no legend is given, the field is removed everywhere.
     */
    public function removeField(array|string $name, string|null $legend = null): self
    {
        $parents = (array) $legend;

        foreach ($this->config as $key => $group) {
            if ([] === $parents || \in_array($key, $parents, true)) {
                $this->config[$key]['fields'] = array_diff($group['fields'], (array) $name);
            }
        }

        return $this;
    }

    private function addFieldsToLegend(array $fields, array $parents, string $position, \Closure|array|string|null $fallback, string $fallbackPosition, bool $skipLegends): void
    {
        // If $skipLegends is true, we usually only have one legend without name, so we
        // simply append to that
        if ($skipLegends) {
            if (self::POSITION_PREPEND === $position) {
                $parents = [array_key_first($this->config)];
            } else {
                $parents = [array_key_last($this->config)];
            }
        }

        if ($this->addFieldsToParent($fields, $parents, $position)) {
            return;
        }

        $this->addFallback($fields, $parents, $position, $fallback, $fallbackPosition, $skipLegends);
    }

    private function addFieldsToParent(array $fields, array $parents, string $position): bool
    {
        foreach ($parents as $parent) {
            if (\array_key_exists($parent, $this->config)) {
                $offset = self::POSITION_PREPEND === $position ? 0 : \count($this->config[$parent]['fields']);
                array_splice($this->config[$parent]['fields'], $offset, 0, $fields);

                return true;
            }
        }

        return false;
    }

    private function addFieldsToField(array $fields, array $parents, string $position, \Closure|array|string|null $fallback, string $fallbackPosition, bool $skipLegends): void
    {
        $offset = (int) (self::POSITION_AFTER === $position);

        foreach ($parents as $parent) {
            $legend = $this->getLegendForField($parent);

            if (null !== $legend) {
                $offset += array_search($parent, $this->config[$legend]['fields'], true);
                array_splice($this->config[$legend]['fields'], $offset, 0, $fields);

                return;
            }
        }

        $this->addFallback($fields, $parents, $position, $fallback, $fallbackPosition, $skipLegends);
    }

    private function addFallback(array $fields, array $parents, string $position, \Closure|array|string|null $fallback, string $fallbackPosition, bool $skipLegends): void
    {
        if (\is_callable($fallback)) {
            $fallback($this->config, ['fields' => $fields, 'parents' => $parents, 'position' => $position, 'fallback' => $fallback, 'fallbackPosition' => $fallbackPosition], $skipLegends);

            return;
        }

        if (null !== $fallback) {
            $fallback = (array) $fallback;

            if ($this->addFieldsToParent($fields, $fallback, $fallbackPosition)) {
                return;
            }

            // If the fallback palette was not found, create a new one
            $legend = reset($fallback);

            $this->addLegend($legend, null, self::POSITION_APPEND);
        }

        // If everything fails, add to the last legend
        $legend = array_key_last($this->config);
        $offset = self::POSITION_PREPEND === $fallbackPosition ? 0 : \count($this->config[$legend]['fields']);
        array_splice($this->config[$legend]['fields'], $offset, 0, $fields);
    }

    /**
     * Converts a palette string to a configuration array.
     *
     * @return array<string|int, array{fields: array<string>, hide: bool}>
     */
    private function explode(string $palette): array
    {
        if ('' === $palette) {
            return [['fields' => [], 'hide' => false]];
        }

        $legendCount = 0;
        $config = [];
        $groups = StringUtil::trimsplit(';', $palette);

        foreach ($groups as $group) {
            if ('' === $group) {
                continue;
            }

            $hide = false;
            $fields = StringUtil::trimsplit(',', $group);

            if (preg_match('#{(.+?)(:collapsed|:hide)?}#', (string) $fields[0], $matches)) {
                $legend = $matches[1];
                $hide = isset($matches[2]);
                array_shift($fields);
            } else {
                $legend = $legendCount++;
            }

            $config[$legend] = ['fields' => $fields, 'hide' => $hide];
        }

        // Make sure there is at least one legend
        if ([] === $config) {
            $config = [['fields' => [], 'hide' => false]];
        }

        return $config;
    }

    /**
     * Converts a configuration array to a palette string.
     */
    private function implode(): string
    {
        $palette = '';

        foreach ($this->config as $legend => $group) {
            if (\count($group['fields']) < 1) {
                continue;
            }

            if ('' !== $palette) {
                $palette .= ';';
            }

            if (!\is_int($legend)) {
                $palette .= \sprintf('{%s%s},', $legend, $group['hide'] ? ':hide' : '');
            }

            $palette .= implode(',', $group['fields']);
        }

        return $palette;
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
}
