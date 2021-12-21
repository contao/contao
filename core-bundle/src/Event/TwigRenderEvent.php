<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Event;

use Contao\CoreBundle\Twig\Transformer\PostRenderTransformerInterface;

class TwigRenderEvent
{
    private string $name;
    private array $context;

    /**
     * @var array<PostRenderTransformerInterface>
     */
    private array $transformers = [];

    public function __construct(string $name, array $context)
    {
        $this->name = $name;
        $this->context = $context;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function setContext(array $context): void
    {
        $this->context = $context;
    }

    public function addPostRenderTransformer(PostRenderTransformerInterface $transformer): void
    {
        $this->transformers[] = $transformer;
    }

    /**
     * @return array<PostRenderTransformerInterface>
     */
    public function getPostRenderTransformers(): array
    {
        return $this->transformers;
    }
}
