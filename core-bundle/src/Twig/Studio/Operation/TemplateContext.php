<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Studio\Operation;

/**
 * @experimental
 */
class TemplateContext
{
    /**
     * @internal
     *
     * @param array<string, mixed> $parameters
     */
    public function __construct(
        private readonly string $identifier,
        private readonly string $extension,
        public readonly array $parameters = [],
    ) {
    }

    public function getParameter(string $parameter, mixed $default = null): mixed
    {
        return $this->parameters[$parameter] ?? $default;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getExtension(): string
    {
        return $this->extension;
    }

    public function getUserTemplatesStoragePath(): string
    {
        return "$this->identifier.$this->extension";
    }

    public function getManagedNamespaceName(): string
    {
        return "@Contao/$this->identifier.$this->extension";
    }
}
