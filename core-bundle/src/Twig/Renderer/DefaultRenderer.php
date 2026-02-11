<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Twig\Renderer;

use Twig\Environment;
use Twig\TemplateWrapper;

/**
 * @experimental
 */
final class DefaultRenderer implements RendererInterface
{
    public function __construct(private readonly Environment $twig)
    {
    }

    public function render(TemplateWrapper|string $name, array $parameters = []): string
    {
        return $this->twig->render($name, $parameters);
    }
}
