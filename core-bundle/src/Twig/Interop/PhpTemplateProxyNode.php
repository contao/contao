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

use Contao\CoreBundle\Twig\Extension\ContaoExtension;
use Twig\Compiler;
use Twig\Node\Node;

/**
 * @experimental
 */
final class PhpTemplateProxyNode extends Node
{
    public function compile(Compiler $compiler): void
    {
        // echo $this->extensions["Contao\\…\\ContaoExtension"]->renderLegacyTemplate(
        //     $this->getTemplateName(),
        //     array_map(
        //         function(callable $block) use ($context): string {
        //             if ($this->env->isDebug()) { ob_start(); } else { ob_start(static function () { return ''; }); }
        //             try { $block($context); return ob_get_contents(); } finally { ob_end_clean(); }
        //         }, $blocks
        //     ), $context
        // );
        $compiler
            ->write('echo $this->extensions[')
            ->repr(ContaoExtension::class)
            ->raw(']->renderLegacyTemplate('."\n")
            ->indent()
            ->write('$this->getTemplateName(),'."\n")
            ->write('array_map('."\n")
            ->indent()
            ->write('function(callable $block) use ($context): string {'."\n")
            ->indent()
            ->write('if ($this->env->isDebug()) { ob_start(); } else { ob_start(static function () { return \'\'; }); }'."\n")
            ->write('try { $block($context); return ob_get_contents(); } finally { ob_end_clean(); }'."\n")
            ->outdent()
            ->write('}, $blocks'."\n")
            ->outdent()
            ->write('), $context'."\n")
            ->outdent()
            ->write(');'."\n")
        ;
    }
}
