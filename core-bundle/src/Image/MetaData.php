<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Image;

/**
 * @psalm-immutable
 */
final class MetaData
{
    /**
     * @var array
     */
    private $metaFields;

    /**
     * @var array
     */
    private $values;

    public function __construct(array $values, array $metaFields)
    {
        $this->metaFields = array_unique($metaFields);

        // strip superfluous
        $this->values = array_intersect_key($values, array_flip($this->metaFields));
    }

    public function withOverwrites(self $metaData): self
    {
        return new self(
            array_merge($this->values, $metaData->values),
            array_unique(array_merge($this->metaFields, $metaData->metaFields))
        );
    }

    /**
     * @return mixed|null
     */
    public function get(string $key)
    {
        return $this->values[$key] ?? null;
    }

    public function getAlt(): ?string
    {
        return $this->get('alt');
    }

    public function getAll(): array
    {
        // provide possibly missing fields (empty values)
        $values = array_merge(
            array_combine($this->metaFields, array_fill(0, \count($this->metaFields), '')),
            $this->values
        );

        // normalize + handle special cases
        // fixme
        $values['href'] = $values['link'] ?? '';

        return $values;
    }
}
