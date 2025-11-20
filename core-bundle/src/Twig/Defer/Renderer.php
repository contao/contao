<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Twig\Defer;

use Twig\Environment;
use Twig\TemplateWrapper;

/**
 * @experimental
 */
class Renderer
{
    public function __construct(private readonly Environment $twig)
    {
    }

    public function render(TemplateWrapper|string $name, array $context = []): string
    {
        $chunks = [];

        // Stream and resolve all content except for the deferred output
        foreach ($this->twig->load($name)->stream($context) as $value) {
            $chunks[] = $value instanceof DeferredStringable ? $value : (string) $value;
        }

        // Finally, also resolve the deferred output
        return implode('', $chunks);
    }
}
