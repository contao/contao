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

use Contao\CoreBundle\Twig\Loader\ThemeNamespace;

/**
 * @experimental
 */
class OperationContext
{
    /**
     * @internal
     */
    public function __construct(
        private readonly ThemeNamespace $themeNamespace,
        private readonly string $identifier,
        private readonly string $extension,
        private readonly string|null $themeSlug,
    ) {
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getExtension(): string
    {
        return $this->extension;
    }

    public function isThemeContext(): bool
    {
        return null !== $this->themeSlug;
    }

    public function getThemeSlug(): string|null
    {
        return $this->themeSlug;
    }

    public function getUserTemplatesStoragePath(): string
    {
        $path = "$this->identifier.$this->extension";

        if (!$this->isThemeContext()) {
            return $path;
        }

        return "{$this->themeNamespace->getPath($this->themeSlug)}/$path";
    }

    public function getManagedNamespaceName(): string
    {
        return "@Contao/$this->identifier.$this->extension";
    }
}
