<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\File;

/**
 * This class acts as a collection for \Contao\CoreBundle\File\Metadata
 * instances of different locales for the same entity.
 *
 * @implements \ArrayAccess<string, Metadata>
 */
class MetadataBag implements \ArrayAccess
{
    /**
     * @param array<string, Metadata> $metadata       Metadata objects, keyed by the locale
     * @param array<string>           $defaultLocales default locales in the order they should be tried
     */
    public function __construct(
        private readonly array $metadata,
        private readonly array $defaultLocales = [],
    ) {
        foreach ($metadata as $item) {
            if (!$item instanceof Metadata) {
                throw new \TypeError(sprintf('The metadata bag can only contain elements of type %s, got %s.', Metadata::class, get_debug_type($item)));
            }
        }

        foreach ($defaultLocales as $locale) {
            if (!\is_string($locale)) {
                throw new \TypeError(sprintf('The metadata bag can only be constructed with default locales of type string, got %s.', get_debug_type($locale)));
            }
        }
    }

    public function get(string ...$locales): Metadata|null
    {
        foreach ($locales as $locale) {
            if ($metadata = $this->metadata[$locale] ?? null) {
                return $metadata;
            }
        }

        return null;
    }

    public function getDefault(): Metadata|null
    {
        return $this->get(...$this->defaultLocales);
    }

    public function getFirst(): Metadata|null
    {
        return $this->metadata[array_key_first($this->metadata)] ?? null;
    }

    /**
     * @return array<string, Metadata>
     */
    public function all(): array
    {
        return $this->metadata;
    }

    /**
     * Returns true if metadata is present for any of the given locales.
     */
    public function has(string ...$locales): bool
    {
        foreach (array_keys($this->metadata) as $locale) {
            if (\in_array($locale, $locales, true)) {
                return true;
            }
        }

        return false;
    }

    public function empty(): bool
    {
        return !$this->metadata;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->metadata[$offset]);
    }

    public function offsetGet(mixed $offset): Metadata
    {
        return $this->get($offset) ?? throw new \OutOfBoundsException(sprintf('The locale "%s" does not exist in this metadata bag.', $offset));
    }

    public function offsetSet(mixed $offset, mixed $value): never
    {
        throw new \RuntimeException('Setting metadata is not supported in this metadata bag.');
    }

    public function offsetUnset(mixed $offset): never
    {
        throw new \RuntimeException('Unsetting metadata is not supported in this metadata bag.');
    }
}
