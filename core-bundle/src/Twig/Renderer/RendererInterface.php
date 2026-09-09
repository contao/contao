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

use Twig\TemplateWrapper;

/**
 * @experimental
 */
interface RendererInterface
{
    public function render(TemplateWrapper|string $name, array $parameters = []): string;
}
