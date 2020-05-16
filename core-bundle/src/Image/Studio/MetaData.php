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
 */
final class MetaData
{
    public const PROPERTY_DOMINANT_VALUES = 'overwriteMeta';
    public const PROPERTY_FULLSIZE = 'fullsize';
    public const PROPERTY_MARGIN = 'margin';
    public const PROPERTY_FLOATING = 'floating';

    public const VALUE_ALT = 'alt';
    public const VALUE_CAPTION = 'caption';
    public const VALUE_LINK_TITLE = 'linkTitle';
    public const VALUE_TITLE = 'title';
    public const VALUE_URL = 'link';

    /**
     * @var array
     */
    private $values;

    /**
     * @var array
     */
    private $properties;

    public function __construct(array $values, array $properties = [])
    {
        $this->values = $values;
        $this->properties = array_unique($properties);
    }

    public function withOther(self $metaData, bool $forceMergingValues = false): self
    {
        $values = $forceMergingValues || false !== $metaData->hasDominantValues() ?
            array_merge($this->values, $metaData->values) :
            $this->values;

        return new self(
            $values,
            array_merge($this->properties, $metaData->properties)
        );
    }

    /**
     * @return mixed|null
     */
    public function getValue(string $key)
    {
        $value = $this->values[$key] ?? null;

        if (null === $value) {
            return null;
        }

        return current($this->handleSpecialChars([$key => $value]));
    }

    public function getAlt(): ?string
    {
        return $this->getValue(self::VALUE_ALT);
    }

    public function getCaption(): ?string
    {
        return $this->getValue(self::VALUE_CAPTION);
    }

    public function getLinkTitle(): ?string
    {
        return $this->getValue(self::VALUE_LINK_TITLE);
    }

    public function getTitle(): ?string
    {
        return $this->getValue(self::VALUE_TITLE);
    }

    public function getUrl(): ?string
    {
        return $this->getValue(self::VALUE_URL) ?: null;
    }

    public function getAllValues(): array
    {
        $values = $this->handleSpecialChars($this->values);

        return self::remap($values, [
            self::VALUE_TITLE => 'imageTitle',
            self::VALUE_URL => 'imageUrl',
        ]);
    }

    public function hasDominantValues(): ?bool
    {
        $property = $this->properties[self::PROPERTY_DOMINANT_VALUES] ?? null;

        return null !== $property ? (bool) $property : null;
    }

    public function shouldDisplayFullSize(): ?bool
    {
        $property = $this->properties[self::PROPERTY_FULLSIZE] ?? null;

        return null !== $property ? (bool) $property : null;
    }

    public function getFloatingProperty(): ?string
    {
        return$this->properties[self::PROPERTY_FLOATING] ?? null;
    }

    public function getMarginProperty(): ?array
    {
        return $this->properties[self::PROPERTY_MARGIN] ?? null;
    }

    public static function remap(array $values, array $mapping): array
    {
        foreach (array_intersect_key($mapping, $values) as $from => $to) {
            $values[$to] = $values[$from];
            unset($values[$from]);
        }

        return $values;
    }

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
