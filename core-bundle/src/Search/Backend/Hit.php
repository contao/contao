<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Search\Backend;

use Contao\CoreBundle\Image\Studio\FigureBuilder;

/**
 * @experimental
 */
final class Hit
{
    private string|null $editUrl = null;

    private string|null $context = null;

    private FigureBuilder|null $imageFigureBuilder = null;

    private array $metadata = [];

    public function __construct(
        private readonly Document $document,
        private readonly string $title,
        private readonly string $viewUrl,
    ) {
    }

    public function getDocument(): Document
    {
        return $this->document;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getViewUrl(): string
    {
        return $this->viewUrl;
    }

    public function getEditUrl(): string|null
    {
        return $this->editUrl;
    }

    public function getContext(): string|null
    {
        return $this->context;
    }

    public function getImageFigureBuilder(): FigureBuilder|null
    {
        return $this->imageFigureBuilder;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function withEditUrl(string $editUrl): self
    {
        $clone = clone $this;
        $clone->editUrl = $editUrl;

        return $clone;
    }

    public function withContext(string $context): self
    {
        $clone = clone $this;
        $clone->context = $context;

        return $clone;
    }

    public function withImageFigureBuilder(FigureBuilder $figureBuilder): self
    {
        $clone = clone $this;
        $clone->imageFigureBuilder = $figureBuilder;

        return $clone;
    }

    public function withMetadata(array $metadata): self
    {
        $clone = clone $this;
        $clone->metadata = $metadata;

        return $clone;
    }
}
