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

use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Node;

/**
 * @experimental
 */
#[YieldReady]
final class PhpTemplateProxyNode extends Node
{
    public function __construct(string $extensionName)
    {
        parent::__construct([], ['extension_name' => $extensionName]);
    }

    /**
     * @todo Remove output buffer handling once Twig is yield-only (probably version 4.0)
     */
    public function compile(Compiler $compiler): void
    {
        /** @see PhpTemplateProxyNodeTest::testCompilesProxyCode() */
        $compiler
            ->write(class_exists(YieldReady::class) ? 'yield' : 'echo') // Backwards compatibility
            ->write(' $this->extensions[')
            ->repr($this->getAttribute('extension_name'))
            ->raw(']->renderLegacyTemplate('."\n")
            ->indent()
            ->write('$this->getTemplateName(),'."\n")
            ->write('array_map('."\n")
            ->indent()
            ->write('function(callable $block) use ($context): string {'."\n")
            ->indent()
            ->write('if ($this->env->isDebug()) { ob_start(); } else { ob_start(static function () { return \'\'; }); }'."\n")
            ->write('try {'."\n")
            ->indent()
            ->write('$content = \'\';'."\n")
            ->write('foreach ($block($context) ?? [\'\'] as $chunk) {'."\n")
            ->indent()
            ->write('$content .= ob_get_contents() . $chunk;'."\n")
            ->write('ob_clean();'."\n")
            ->outdent()
            ->write('}'."\n")
            ->write('return $content . ob_get_contents();'."\n")
            ->outdent()
            ->write('} finally { ob_end_clean(); }'."\n")
            ->outdent()
            ->write('}, array_intersect_key($blocks, $this->blocks)'."\n")
            ->outdent()
            ->write('), $context'."\n")
            ->outdent()
            ->write(');'."\n")
        ;
    }
}
