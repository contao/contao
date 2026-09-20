<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

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
