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

use Contao\CoreBundle\Twig\ContaoTwigUtil;
use Twig\Error\Error;
use Twig\Error\RuntimeError;
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
        private readonly array $blockNames = [],
        private readonly array $slots = [],
        private readonly string|null $extends = null,
        private readonly array $uses = [],
        private readonly Error|null $error = null,
        private readonly array $deprecations = [],
    ) {
    }

    public function getName(): string
    {
        return $this->source->getName();
    }

    public function isComponent(): bool
    {
        return str_starts_with(ContaoTwigUtil::getIdentifier($this->getName()), 'component/');
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
     * @return list<array{string, array<string, string>}>
     */
    public function getUses(): array
    {
        return $this->uses;
    }

    public function isUsing(string $logicalName): bool
    {
        foreach ($this->uses as [$name, $importMap]) {
            if ($name === $logicalName) {
                return true;
            }
        }

        return false;
    }

    public function getError(): Error|null
    {
        return $this->error;
    }

    /**
     * Returns false if there is a compile-time error regarding this template.
     */
    public function hasValidInformation(): bool
    {
        return !$this->error || $this->error instanceof RuntimeError;
    }

    /**
     * @return list<array{line: int, message: string}>
     */
    public function getDeprecations(): array
    {
        return $this->deprecations;
    }
}
