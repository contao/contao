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

use Contao\CoreBundle\Exception\InvalidResourceException;
use Contao\CoreBundle\File\Metadata;
use Contao\CoreBundle\Image\Studio\Figure;
use Contao\CoreBundle\Image\Studio\FigureBuilder;
use Contao\CoreBundle\Image\Studio\ImageResult;
use Contao\FilesModel;
use Contao\Image\ImageInterface;

class FigureBuilderStub extends FigureBuilder
{
    private string|null $path = null;
    private Metadata|null $metadata = null;

    /**
     * @param array<string, ImageResult> $imageMap
     */
    public function __construct(private readonly array $imageMap)
    {
        // do not call parent constructor
    }

    public function fromPath(string $path, bool $autoDetectDbafsPaths = true): FigureBuilder
    {
        $this->path = $path;

        return $this;
    }

    public function fromFilesModel(FilesModel $filesModel): FigureBuilder
    {
        $this->path = $filesModel->path;

        return $this;
    }

    public function fromUuid(string $uuid): FigureBuilder
    {
        throw new \RuntimeException('not implemented');
    }

    public function fromId(int $id): FigureBuilder
    {
        throw new \RuntimeException('not implemented');
    }

    public function fromImage(ImageInterface $image): FigureBuilder
    {
        throw new \RuntimeException('not implemented');
    }

    public function setMetadata(?Metadata $metadata): FigureBuilder
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function build(): Figure
    {
        if (null === $this->path) {
            throw new InvalidResourceException('No path set.');
        }

        return new Figure($this->imageMap[$this->path], $this->metadata);
    }
}
