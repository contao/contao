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
     * @param list<string> $blockNames
     * @param list<string> $slots
     *
     * @internal
     */
    public function __construct(
        private readonly Source $source,
        private readonly array $blockNames,
        private readonly array $slots,
        private readonly string|null $extends,
        private readonly array $uses,
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
    public function getBlockNames(): array
    {
        return $this->blockNames;
    }

    /**
     * @return list<string>
     */
    public function getSlots(): array
    {
        return $this->slots;
    }

    public function getExtends(): string|null
    {
        return $this->extends;
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function getUses(): array
    {
        return $this->uses;
    }
}
