<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Event;

use Contao\CoreBundle\Twig\Extension\ContaoExtension;
use Twig\Compiler;
use Twig\Node\Node;
use Twig\Node\NodeOutputInterface;

/**
 * @experimental
 */
final class RenderEventNode extends Node implements NodeOutputInterface
{
    public function compile(Compiler $compiler): void
    {
        // $context = $this->extensions["Contao\\…\\ContaoExtension"]->dispatchRenderEvent($this->getTemplateName(), $context);
        $compiler
            ->write('$context = $this->extensions[')
            ->repr(ContaoExtension::class)
            ->raw(']->dispatchRenderEvent($this->getTemplateName(), $context);'."\n\n")
        ;
    }
}
