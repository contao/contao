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

use Twig\Source;

/**
 * @experimental
 */
final class TemplateInformation
{
    /**
     * @param list<string> $blocks
     *
     * @internal
     */
    public function __construct(
        private readonly Source $source,
        private readonly array $blocks,
    ) {
    }

    public function getName(): string
    {
        return $this->source->getName();
    }

    public function getCode(): string
    {
        return $this->source->getCode();
    }

    /**
     * @return list<string>
     */
    public function getBlocks(): array
    {
        return $this->blocks;
    }
}
