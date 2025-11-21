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
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Node;
use Twig\Node\NodeOutputInterface;

/**
 * @internal
 */
#[YieldReady]
final class PhpTemplateParentReferenceNode extends Node implements NodeOutputInterface
{
    public function compile(Compiler $compiler): void
    {
        /** @see PhpTemplateParentReferenceNodeTest::testCompilesParentReferenceCode() */
        $compiler
            ->write('yield sprintf(\'[[TL_PARENT_%s]]\', \\')
            ->raw(ContaoFramework::class)
            ->raw('::getNonce());'."\n")
        ;
    }
}
