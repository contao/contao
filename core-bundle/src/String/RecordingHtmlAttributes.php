<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\String;

/**
 * Records mutations with eagerly captured arguments and replays them on
 * reads or merges.
 */
class RecordingHtmlAttributes extends HtmlAttributes
{
    /**
     * @var list<array{string, list<mixed>}>
     */
    private array $calls = [];

    private HtmlAttributes|null $cachedAttributes = null;

    /**
     * @var array<string, true>
     */
    private array $touchedAttributes = [];

    public function mergeWith(HtmlAttributes|iterable|string|null $attributes = null, mixed $condition = true): self
    {
        if (empty($attributes) || !$this->test($condition)) {
            return $this;
        }

        if ($attributes instanceof self) {
            $this->calls = [...$this->calls, ...$attributes->calls];
            $this->cachedAttributes = null;
            $this->touchedAttributes = [];

            return $this;
        }

        if ($attributes instanceof HtmlAttributes) {
            // Preserve trusted-instance merging without retaining the mutable source.
            return $this->record(__FUNCTION__, [new HtmlAttributes($attributes)]);
        }

        if (\is_string($attributes)) {
            return $this->record(__FUNCTION__, [$attributes]);
        }

        foreach ($attributes as $name => $value) {
            $this->validateAttributeName(strtolower((string) $name));

            if (false === $value) {
                $this->set($name, false);
            } else {
                // Record each entry separately to retain duplicate iterable keys.
                $this->record(__FUNCTION__, [[$name => self::capture($value)]]);
            }
        }

        return $this;
    }

    public function set(string $name, \Stringable|bool|float|int|string|null $value = true, mixed $condition = true): self
    {
        if (!$this->test($condition)) {
            return $this;
        }

        $this->validateAttributeName($name);

        return $this->record(__FUNCTION__, [$name, self::capture($value)]);
    }

    public function setIfExists(string $name, \Stringable|bool|float|int|string|null $value): self
    {
        $value = $value instanceof \Stringable ? (string) $value : $value;

        return $this->set($name, $value, $value);
    }

    public function unset(string $name, mixed $condition = true): self
    {
        return $this->test($condition) ? $this->record(__FUNCTION__, [$name]) : $this;
    }

    public function addClass(array|string $classes, mixed $condition = true): self
    {
        return $this->test($condition) ? $this->record(__FUNCTION__, [self::capture($classes)]) : $this;
    }

    public function removeClass(array|string $classes, mixed $condition = true): self
    {
        return $this->test($condition) ? $this->record(__FUNCTION__, [self::capture($classes)]) : $this;
    }

    public function addStyle(array|string $styles, mixed $condition = true): self
    {
        return $this->test($condition) ? $this->record(__FUNCTION__, [self::capture($styles)]) : $this;
    }

    public function removeStyle(array|string $styles, mixed $condition = true): self
    {
        return $this->test($condition) ? $this->record(__FUNCTION__, [self::capture($styles)]) : $this;
    }

    public function toString(bool $leadingSpace = true): string
    {
        return $this->getAttributes()->toString($leadingSpace);
    }

    /**
     * @return \Generator<string, string|false>
     */
    public function getIterator(): \Generator
    {
        $iterator = static function ($parentIterator, $touchedAttributes): \Generator {
            foreach ($parentIterator as $name => $value) {
                yield $name => $value;

                unset($touchedAttributes[$name]);
            }

            foreach (array_keys($touchedAttributes) as $name) {
                yield (string) $name => false;
            }
        };

        return $iterator($this->getAttributes()->getIterator(), $this->touchedAttributes);
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->getAttributes()->offsetExists($offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->getAttributes()->offsetGet($offset);
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->record(__FUNCTION__, [self::capture($offset)]);
    }

    public function jsonSerialize(): object
    {
        return $this->getAttributes()->jsonSerialize();
    }

    /**
     * @return array<string, true>
     */
    protected function replay(HtmlAttributes $attributes): array
    {
        $touchedAttributes = [];

        foreach ($this->calls as [$method, $arguments]) {
            if ('mergeWith' !== $method) {
                $touchedAttributes[match ($method) {
                    'addClass', 'removeClass' => 'class',
                    'addStyle', 'removeStyle' => 'style',
                    default => strtolower($arguments[0]),
                }] = true;
            }

            $attributes->$method(...$arguments);
        }

        return $touchedAttributes;
    }

    /**
     * @param list<mixed> $arguments
     */
    private function record(string $method, array $arguments): self
    {
        $this->calls[] = [$method, $arguments];
        $this->cachedAttributes = null;
        $this->touchedAttributes = [];

        return $this;
    }

    private function getAttributes(): HtmlAttributes
    {
        if (!$this->cachedAttributes) {
            $attributes = new HtmlAttributes();
            $this->touchedAttributes = $this->replay($attributes);
            $this->cachedAttributes = $attributes;
        }

        return $this->cachedAttributes;
    }

    /**
     * Resolve all Stringable objects to strings.
     */
    private static function capture(mixed $value): mixed
    {
        if ($value instanceof \Stringable) {
            return (string) $value;
        }

        if (\is_array($value)) {
            return array_map(self::capture(...), $value);
        }

        return $value;
    }
}
