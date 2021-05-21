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
 * This class is as a container for file metadata as typically defined in
 * tl_files/tl_content. Its underlying data structure is a key-value store with
 * added getters/setters for convenience.
 *
 * The data must be stored in a normalized form. It is your responsibility to
 * ensure this is the case when creating an instance of this class. You can use
 * the public class constants as keys for a better DX.
 */
class Metadata
{
    public const VALUE_ALT = 'alt';
    public const VALUE_CAPTION = 'caption';
    public const VALUE_TITLE = 'title';
    public const VALUE_URL = 'link';
    public const VALUE_UUID = 'uuid';

    /**
     * Key-value pairs of metadata.
     *
     * @var array<string, mixed>
     */
    private $values;

    /**
     * @param array<string, mixed> $values
     */
    public function __construct(array $values)
    {
        $this->values = $values;
    }

    /**
     * Returns a new metadata representation that also contains the given
     * values. Existing keys will be overwritten.
     *
     * @param array<string, mixed> $values
     */
    public function with(array $values): self
    {
        if (empty($values)) {
            return $this;
        }

        return new self(array_merge($this->values, $values));
    }

    /**
     * Returns a value or null if the value was not found.
     */
    public function get(string $key)
    {
        return $this->values[$key] ?? null;
    }

    public function getAlt(): string
    {
        return $this->values[self::VALUE_ALT] ?? '';
    }

    public function getCaption(): string
    {
        return $this->values[self::VALUE_CAPTION] ?? '';
    }

    public function getTitle(): string
    {
        return $this->values[self::VALUE_TITLE] ?? '';
    }

    public function getUrl(): string
    {
        return $this->values[self::VALUE_URL] ?? '';
    }

    /**
     * Returns a UUID reference in ASCII format or null if not set.
     */
    public function getUuid(): ?string
    {
        return $this->values[self::VALUE_UUID] ?? null;
    }

    /**
     * Returns true if this container contains a given value, false otherwise.
     */
    public function has(string $key): bool
    {
        return isset($this->values[$key]);
    }

    /**
     * Returns the whole data set as an associative array.
     */
    public function all(): array
    {
        return $this->values;
    }

    /**
     * Returns true if this container holds no values, false otherwise.
     */
    public function empty(): bool
    {
        return empty($this->values);
    }
}
