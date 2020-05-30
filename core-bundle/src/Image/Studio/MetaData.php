<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Image\Studio;

use Contao\StringUtil;

/**
 * @psalm-immutable
 *
 * This class is as a container for image meta data as typically defined in
 * tl_files / tl_content. It's underlying data structure is a key-value store
 * with added getters/setters and special char formatting for convenience.
 *
 * The data must be stored in a normalized form. It's your responsibility to
 * ensure this is the case when creating an instance of this class. You can
 * use the public class constants as keys for a better DX.
 */
final class MetaData
{
    public const VALUE_ALT = 'alt';
    public const VALUE_CAPTION = 'caption';
    public const VALUE_LINK_TITLE = 'linkTitle';
    public const VALUE_TITLE = 'title';
    public const VALUE_URL = 'link';

    /**
     * Key-value pairs of meta data.
     *
     * @var array<string, mixed>
     */
    private $values;

    public function __construct(array $values)
    {
        $this->values = $values;
    }

    /**
     * Get a new meta data container that is the result of merging this
     * container's data with the data of the specified one.
     */
    public function withOther(self $metaData): self
    {
        return new self(array_merge($this->values, $metaData->values));
    }

    /**
     * Get a value. Returns null if the value was not found.
     *
     * @return mixed|null
     */
    public function get(string $key)
    {
        $value = $this->values[$key] ?? null;

        if (null === $value) {
            return null;
        }

        return current($this->handleSpecialChars([$key => $value]));
    }

    public function getAlt(): ?string
    {
        return $this->get(self::VALUE_ALT);
    }

    public function getCaption(): ?string
    {
        return $this->get(self::VALUE_CAPTION);
    }

    public function getLinkTitle(): ?string
    {
        return $this->get(self::VALUE_LINK_TITLE);
    }

    public function getTitle(): ?string
    {
        return $this->get(self::VALUE_TITLE);
    }

    public function getUrl(): ?string
    {
        return $this->get(self::VALUE_URL) ?: null;
    }

    /**
     * Return the whole data set as an associative array.
     *
     * Note that this representation is optimized for the use in Contao
     * templates and therefore contains special key names that differ from
     * the internal normalized representation.
     */
    public function getAll(): array
    {
        $values = $this->handleSpecialChars($this->values);

        return self::remap($values, [
            self::VALUE_TITLE => 'imageTitle',
            self::VALUE_URL => 'imageUrl',
        ]);
    }

    /**
     * @psalm-pure
     *
     * Modify the name of array keys by a given mapping.
     *
     * Example:
     *   $values = ['a' => 1, 'b' => 2];
     *   remap($values, ['b' => 'foo']); // ['a' => 1, 'foo' => 2]
     */
    public static function remap(array $values, array $mapping): array
    {
        foreach (array_intersect_key($mapping, $values) as $from => $to) {
            $values[$to] = $values[$from];
            unset($values[$from]);
        }

        return $values;
    }

    /**
     * @psalm-pure
     * @psalm-suppress ImpureMethodCall
     *
     * Apply `StringUtil::specialchars()` to a known list of candidates.
     */
    private function handleSpecialChars(array $values): array
    {
        $candidates = [
            self::VALUE_ALT,
            self::VALUE_TITLE,
            self::VALUE_LINK_TITLE,
            self::VALUE_CAPTION,
        ];

        foreach (array_intersect_key($candidates, $values) as $key => $value) {
            $values[$key] = StringUtil::specialchars($value);
        }

        return $values;
    }
}
