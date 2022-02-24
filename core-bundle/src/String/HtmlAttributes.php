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
 * @implements \IteratorAggregate<string, string>
 * @implements \ArrayAccess<string, string|int|bool|\Stringable|null>
 */
class HtmlAttributes implements \Stringable, \IteratorAggregate, \ArrayAccess
{
    /**
     * @var array<string, string>
     */
    private array $attributes = [];

    /**
     * @param iterable<string, string|int|bool|\Stringable|null> $attributes
     */
    public function __construct(iterable $attributes = [])
    {
        foreach ($attributes as $name => $value) {
            $this->set($name, $value);
        }
    }

    /**
     * Outputs the attributes as a string that is safe to be placed inside HTML
     * tags. The output will contain a leading space if there is at least one
     * property set: e.g. ' foo="bar" bar="42"'.
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Parse attributes from an attribute string like 'foo="bar" baz="42'.
     */
    public static function fromString(string $attributesString): self
    {
        // Regular expression to match attributes according to https://html.spec.whatwg.org/#before-attribute-name-state
        $attributeRegex = <<<'EOD'
            (
                [\s/]*+                                 # Optional white space including slash
                ([^>\s/][^>\s/=]*+)                     # Attribute name
                [\s]*+                                  # Optional white space
                (?:=                                    # Assignment
                    [\s]*+                              # Optional white space
                    (?|                                 # Value
                        "([^"]*)(?:"|$(*SKIP)(*FAIL))   # Double quoted value
                        |'([^']*)(?:'|$(*SKIP)(*FAIL))  # Or single quoted value
                        |([^\s>]*+)                     # Or unquoted or missing value
                    )                                   # Value end
                )?+                                     # Assignment is optional
            )ix
            EOD;

        preg_match_all($attributeRegex, $attributesString, $matches, PREG_SET_ORDER | PREG_UNMATCHED_AS_NULL);

        $instance = new self();

        foreach ($matches as [1 => $name, 2 => $value]) {
            try {
                $instance->set($name, html_entity_decode($value ?? '', ENT_QUOTES));
            } catch (\InvalidArgumentException $exception) {
                // Skip invalid attributes
            }
        }

        return $instance;
    }

    /**
     * Sets a property and validates the name. If the given $value is false the
     * property will be unset instead. All values will be coerced to strings,
     * whereby null and true will result in an empty string.
     */
    public function set(string $name, string|int|bool|\Stringable|null $value): self
    {
        $name = strtolower($name);

        if (1 !== preg_match('/^[a-z](?:[_-]?[a-z0-9])*$/', $name)) {
            throw new \InvalidArgumentException(sprintf('A HTML attribute name must only consist of the characters [a-z0-9_-], must start with a letter, must not end with a underscore/hyphen and must not contain two underscores/hyphens in a row, got "%s".', $name));
        }

        // Unset if value is set to false
        if (false === $value) {
            unset($this->attributes[$name]);

            return $this;
        }

        $this->attributes[$name] = true === $value ? '' : (string) $value;

        // Normalize class names
        if ('class' === $name) {
            $this->addClass();
        }

        return $this;
    }

    public function setIfExists(string $name, string|int|bool|\Stringable|null $value): self
    {
        if (!empty($value)) {
            $this->set($name, $value);
        }

        return $this;
    }

    public function unset(string $key): self
    {
        unset($this->attributes[$key]);

        return $this;
    }

    public function addClass(string ...$classes): self
    {
        $this->attributes['class'] = implode(
            ' ',
            array_unique(
                array_filter(
                    array_merge(
                        explode(' ', $this->attributes['class'] ?? ''),
                        array_map('trim', $classes)
                    )
                )
            )
        );

        return $this;
    }

    public function removeClass(string ...$classes): self
    {
        $this->attributes['class'] = implode(
            ' ',
            array_filter(
                array_diff(
                    explode(' ', $this->attributes['class'] ?? ''),
                    array_filter(array_map('trim', $classes))
                )
            )
        );

        return $this;
    }

    /**
     * Outputs the attributes as a string that is safe to be placed inside HTML
     * tags. The output will contain a leading space if $leadingSpace is set to
     * true and there is at least one property set: e.g. ' foo="bar" bar="42"'.
     */
    public function toString(bool $leadingSpace = true): string
    {
        $attributes = [];

        foreach ($this->attributes as $name => $value) {
            $attributes[] = '' !== $value ? sprintf('%s="%s"', $name, $this->escapeValue($name, $value)) : $name;
        }

        $string = implode(' ', $attributes);

        return $leadingSpace && $string ? " $string" : $string;
    }

    /**
     * @return \ArrayIterator<string, string>
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->attributes);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->attributes[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        if (!$this->offsetExists($offset)) {
            throw new \OutOfBoundsException(sprintf('The attribute property "%s" does not exist.', $offset));
        }

        return $this->attributes[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->set($offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->attributes[$offset]);
    }

    private function escapeValue(string $name, string $value): string
    {
        if (!preg_match('//u', $value)) {
            throw new \RuntimeException(sprintf('The value of property "%s" is not a valid UTF-8 string.', $name));
        }

        $value = htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE);

        return str_replace(['{{', '}}'], ['&#123;&#123;', '&#125;&#125;'], $value);
    }
}
