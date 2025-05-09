<?php

declare(strict_types=1);

namespace Contao\CoreBundle\DataContainer;

use Contao\StringUtil;

class Palette implements \Stringable
{
    /**
     * @param array<string, array{fields: array<string>, hide: bool}> $config
     */
    private function __construct(private array $config = [])
    {
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * @return array<string, array{fields: array<string>, hide: bool}>
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    public static function fromString(string $palette): self
    {
        return new self(self::explode($palette));
    }

    public function hasLegend(string $legend): bool
    {
        return isset($this->config[$legend]);
    }

    public function toString(): string
    {
        return (new PaletteManipulator())->applyToString($this);
    }

    /**
     * @param string|null $legendFilter Optionally filter for a legend
     */
    public function hasField(string $field, string|null $legendFilter = null): bool
    {
        foreach ($this->config as $legend => $legendConfig) {
            if (null !== $legendFilter && $legend !== $legendFilter) {
                continue;
            }

            if (\in_array($field, $legendConfig['fields'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Converts a palette string to a configuration array.
     *
     * @return array<int|string, array>
     */
    private static function explode(string $palette): array
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
                $hide = isset($matches[2]);
                array_shift($fields);
            } else {
                $legend = $legendCount++;
            }

            $legendMap[$legend] = ['fields' => $fields, 'hide' => $hide];
        }

        return $legendMap;
    }
}
