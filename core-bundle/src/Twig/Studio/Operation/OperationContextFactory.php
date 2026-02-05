<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Twig\Studio\Operation;

use Contao\CoreBundle\Twig\Loader\ThemeNamespace;

/**
 * @experimental
 */
class OperationContextFactory
{
    public function __construct(private readonly ThemeNamespace $themeNamespace)
    {
    }

    public function create(string $identifier, string $extension, string|null $themeSlug): OperationContext
    {
        return new OperationContext($this->themeNamespace, $identifier, $extension, $themeSlug);
    }
}
