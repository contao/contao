<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Image\Studio;

use Contao\CoreBundle\Image\Studio\ImageResult;
use Contao\Image\ImageDimensions;
use Contao\Image\PictureInterface;

class ImageResultStub extends ImageResult
{
    #[\Override]
    public function __construct(
        private readonly array $img,
        private readonly array $sources = [],
    ) {
        // Do not call parent constructor
    }

    #[\Override]
    public function getPicture(): PictureInterface
    {
        throw new \RuntimeException('not implemented');
    }

    #[\Override]
    public function getSources(): array
    {
        return $this->sources;
    }

    #[\Override]
    public function getImg(): array
    {
        return $this->img;
    }

    #[\Override]
    public function getOriginalDimensions(): ImageDimensions
    {
        throw new \RuntimeException('not implemented');
    }

    #[\Override]
    public function createIfDeferred(): void
    {
        throw new \RuntimeException('not implemented');
    }
}
