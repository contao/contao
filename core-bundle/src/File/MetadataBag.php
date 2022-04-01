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
    public function __construct(private readonly array $metadata, private readonly array $defaultLocales = [])
    {
        foreach ($metadata as $item) {
            if (!$item instanceof Metadata) {
                $type = \is_object($item) ? \get_class($item) : \gettype($item);

                throw new \TypeError(sprintf('%s can only contain elements of type %s, got %s.', __CLASS__, Metadata::class, $type));
            }
        }

        foreach ($defaultLocales as $locale) {
            if (!\is_string($locale)) {
                $type = \is_object($locale) ? \get_class($locale) : \gettype($locale);

                throw new \TypeError(sprintf('%s can only be constructed with default locales of type string, got %s.', __CLASS__, $type));
            }
        }
    }

    public function get(string ...$locales): Metadata|null
    {
        foreach ($locales as $locale) {
            if (null !== ($metadata = $this->metadata[$locale] ?? null)) {
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
        return empty($this->metadata);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->metadata[$offset]);
    }

    public function offsetGet(mixed $offset): Metadata
    {
        return $this->get($offset) ?? throw new \OutOfBoundsException(sprintf('The locale "%s" does not exist in this metadata bag.', $offset));
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \RuntimeException(sprintf('Setting metadata to a %s is not supported.', __CLASS__));
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \RuntimeException(sprintf('Unsetting metadata from a %s is not supported.', __CLASS__));
    }
}
