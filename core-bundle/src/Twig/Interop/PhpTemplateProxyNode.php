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

use Twig\Compiler;
use Twig\Node\Node;

class PhpTemplateProxyNode extends Node
{
    public function __construct(string $extensionName)
    {
        parent::__construct([], [
            'extension_name' => $extensionName,
        ]);
    }

    public function compile(Compiler $compiler): void
    {
        /*
         * echo $this->extensions["Contao\\…\\ContaoExtension"]->renderLegacyTemplate(
         *     $this->getTemplateName(),
         *     array_map(
         *         static function(callable $block) use ($context): string {
         *             ob_start(); $block($context); return ob_get_clean();
         *         }, $blocks
         *     ), $context
         * );
         */

        $compiler
            ->write('echo $this->extensions[')
            ->repr($this->getAttribute('extension_name'))
            ->raw(']->renderLegacyTemplate('."\n")
            ->indent()
            ->write('$this->getTemplateName(),'."\n")
            ->write('array_map('."\n")
            ->indent()
            ->write('static function(callable $block) use ($context): string {'."\n")
            ->indent()
            ->write('ob_start(); $block($context); return ob_get_clean();'."\n")
            ->outdent()
            ->write('}, $blocks'."\n")
            ->outdent()
            ->write('), $context'."\n")
            ->outdent()
            ->write(');'."\n")
        ;
    }
}
