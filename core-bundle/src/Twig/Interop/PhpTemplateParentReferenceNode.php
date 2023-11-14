<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Interop;

use Contao\CoreBundle\Framework\ContaoFramework;
use Twig\Compiler;
use Twig\Node\Node;
use Twig\Node\NodeOutputInterface;

/**
 * @experimental
 */
final class PhpTemplateParentReferenceNode extends Node implements NodeOutputInterface
{
    #[\Override]
    public function compile(Compiler $compiler): void
    {
        // echo sprintf('[[TL_PARENT_%s]]', \[…]\ContaoFramework::getNonce());'
        $compiler
            ->write('echo sprintf(\'[[TL_PARENT_%s]]\', \\')
            ->raw(ContaoFramework::class)
            ->raw('::getNonce());'."\n")
        ;
    }
}
