<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Twig\Renderer;

use Contao\CoreBundle\Twig\Defer\DeferredStringable;
use Twig\Environment;
use Twig\TemplateWrapper;

/**
 * @experimental
 */
final class DeferredRenderer implements RendererInterface
{
    public function __construct(private readonly Environment $twig)
    {
    }

    public function render(TemplateWrapper|string $name, array $parameters = []): string
    {
        $chunks = [];

        // Stream and resolve all content except for the deferred output
        foreach ($this->twig->load($name)->stream($parameters) as $value) {
            $chunks[] = $value instanceof DeferredStringable ? $value : (string) $value;
        }

        // Finally, also resolve the deferred output
        return implode('', $chunks);
    }
}
