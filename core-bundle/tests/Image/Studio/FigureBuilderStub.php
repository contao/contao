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
use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Contao\CoreBundle\Image\Studio\Figure;
use Contao\CoreBundle\Image\Studio\FigureBuilder;
use Contao\CoreBundle\Image\Studio\ImageResult;
use Contao\CoreBundle\String\HtmlAttributes;
use Contao\FilesModel;
use Contao\Image\ImageInterface;
use Contao\StringUtil;
use Symfony\Component\Uid\Uuid;

class FigureBuilderStub extends FigureBuilder
{
    private string|null $path = null;
    private Metadata|null $metadata = null;
    private array $linkAttributes = [];

    /**
     * @param array<string, ImageResult> $imageMap
     */
    public function __construct(private readonly array $imageMap, private readonly array $uuidMap = [])
    {
        // Do not call parent constructor
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
        $this->path = $this->uuidMap[StringUtil::binToUuid($uuid)] ?? null;

        return $this;
    }

    public function fromId(int $id): FigureBuilder
    {
        throw new \RuntimeException('not implemented');
    }

    public function fromImage(ImageInterface $image): FigureBuilder
    {
        throw new \RuntimeException('not implemented');
    }

    public function fromStorage(VirtualFilesystemInterface $storage, Uuid|string $location): FigureBuilder
    {
        if (!\is_string($location)) {
            throw new \RuntimeException('not implemented');
        }

        $this->path = "files/$location";

        return $this;
    }

    public function setMetadata(Metadata|null $metadata): FigureBuilder
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function setLinkAttributes(HtmlAttributes|array $attributes): FigureBuilder
    {
        $this->linkAttributes = $attributes instanceof HtmlAttributes ? iterator_to_array($attributes) : $attributes;

        return $this;
    }

    public function build(): Figure
    {
        if (null === $this->path) {
            throw new InvalidResourceException('No path set.');
        }

        return new Figure($this->imageMap[$this->path], $this->metadata, $this->linkAttributes);
    }

    public function buildIfResourceExists(): Figure|null
    {
        if (null === $this->path) {
            return null;
        }

        return new Figure($this->imageMap[$this->path], $this->metadata, $this->linkAttributes);
    }
}
