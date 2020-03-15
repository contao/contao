<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Image\Helper;

final class Picture
{
    /** @var array */
    private $img;

    /** @var array */
    private $sources;

    /** @var string|null */
    private $alt;

    /**
     * @internal
     */
    public function __construct(array $img, array $sources, ?string $alt)
    {
        $this->img = $img;
        $this->sources = $sources;
        $this->alt = $alt;
    }

    public function getImg(): array
    {
        return $this->img;
    }

    public function getSources(): array
    {
        return $this->sources;
    }

    public function getAlt(): ?string
    {
        return $this->alt;
    }

    public function getData(): array
    {
        return [
            'img' => $this->img,
            'sources' => $this->sources,
            'alt' => $this->alt,
        ];
    }
}
