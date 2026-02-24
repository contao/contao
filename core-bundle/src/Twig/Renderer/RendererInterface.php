<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Twig\Renderer;

use Twig\TemplateWrapper;

/**
 * @experimental
 */
interface RendererInterface
{
    public function render(TemplateWrapper|string $name, array $parameters = []): string;
}
