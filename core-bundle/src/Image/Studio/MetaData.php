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

        return array_values($this->handleSpecialChars([$key => $value]))[0];
    }

    public function getAlt(): ?string
    {
        return $this->getValue('alt');
    }

    public function getUrl(): ?string
    {
        return $this->getValue('link') ?: null;
    }

    public function getTitle(): ?string
    {
        return $this->getValue('title');
    }

    public function getAllValues(): array
    {
        $values = $this->handleSpecialChars($this->values);

        // Normalize names
        return self::normalize($values, [
            'title' => 'imageTitle',
            'link' => 'imageUrl',
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

    public static function normalize(array $values, array $mapping, $move = true): array
    {
        foreach (array_intersect_key($mapping, $values) as $from => $to) {
            // todo: remove array conversion if not necessary anymore
            if (preg_match('/^(.*)\[]$/', $to, $matches)) {
                $to = $matches[1];
                $values[$from] = StringUtil::deserialize($values[$from]);
            }

            $values[$to] = $values[$from];

            if ($to !== $from && $move) {
                unset($values[$from]);
            }
        }

        return $values;
    }

    private function handleSpecialChars(array $values): array
    {
        $candidates = ['alt', 'title', 'linkTitle'];

        foreach (array_intersect_key($candidates, $values) as $key => $value) {
            $values[$key] = StringUtil::specialchars($value);
        }

        return $values;
    }
}
