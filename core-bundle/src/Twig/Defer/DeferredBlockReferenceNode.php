<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Defer;

use Contao\CoreBundle\Tests\Twig\Defer\DeferBlockReferenceNodeTest;
use Twig\Compiler;
use Twig\Node\Node;
use Twig\Node\NodeOutputInterface;

/**
 * @internal
 *
 * This node outputs a stringable closure that only returns the block content when
 * being resolved
 *
 * @see \Twig\Node\BlockReferenceNode
 * @see DeferredStringable
 */
final class DeferredBlockReferenceNode extends Node implements NodeOutputInterface
{
    public function __construct(string $name, int $lineno)
    {
        parent::__construct([], ['name' => $name], $lineno);
    }

    /**
     * @see DeferBlockReferenceNodeTest::testCompilesCode()
     */
    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)
            ->write(
                \sprintf(
                    "yield new \\Contao\\CoreBundle\\Twig\\Defer\\DeferredStringable(fn () => implode(iterator_to_array(\$this->unwrap()->yieldBlock('%s', \$context, \$blocks), false)));\n",
                    $this->getAttribute('name'),
                ),
            )
        ;
    }
}
