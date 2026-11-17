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

    public function mergeWith(HtmlAttributes|iterable|string|null $attributes = null, mixed $condition = true): self
    {
        if (empty($attributes) || !$this->test($condition)) {
            return $this;
        }

        if ($attributes instanceof self) {
            $this->calls = [...$this->calls, ...$attributes->calls];
            $this->cachedAttributes = null;

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

            // Record each entry separately to retain duplicate iterable keys.
            $this->record(__FUNCTION__, [[$name => self::capture($value)]]);
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

    public function add(string $name, \Stringable|array|float|int|string|null $values = null, mixed $condition = true, string|null $separator = null): self
    {
        if (!$this->test($condition)) {
            return $this;
        }

        $this->validateAttributeName($name);

        return $this->record(__FUNCTION__, [$name, self::capture($values), true, $separator]);
    }

    public function remove(string $name, \Stringable|array|float|int|string|null $values = null, mixed $condition = true, string|null $separator = null): self
    {
        if (!$this->test($condition)) {
            return $this;
        }

        $this->validateAttributeName($name);

        return $this->record(__FUNCTION__, [$name, self::capture($values), true, $separator]);
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

    public function getIterator(): \Generator
    {
        return $this->getAttributes()->getIterator();
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

    protected function replay(HtmlAttributes $attributes): void
    {
        foreach ($this->calls as [$method, $arguments]) {
            $attributes->$method(...$arguments);
        }
    }

    /**
     * @param list<mixed> $arguments
     */
    private function record(string $method, array $arguments): self
    {
        $this->calls[] = [$method, $arguments];
        $this->cachedAttributes = null;

        return $this;
    }

    private function getAttributes(): HtmlAttributes
    {
        if (!$this->cachedAttributes) {
            $attributes = new HtmlAttributes();
            $this->replay($attributes);

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
