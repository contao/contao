<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Inspector;

/**
 * @experimental
 */
final class BlockInformation implements \Stringable
{
    /**
     * @internal
     */
    public function __construct(
        private readonly string $templateName,
        private readonly string $blockName,
        private readonly BlockType $type,
        private readonly bool $isPrototype = false,
    ) {
    }

    public function __toString(): string
    {
        return $this->getBlockName();
    }

    public function getTemplateName(): string
    {
        return $this->templateName;
    }

    public function getBlockName(): string
    {
        return $this->blockName;
    }

    public function getType(): BlockType
    {
        return $this->type;
    }

    public function isPrototype(): bool
    {
        return $this->isPrototype;
    }
}
