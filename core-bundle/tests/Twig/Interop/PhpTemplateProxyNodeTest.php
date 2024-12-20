<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Interop;

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Extension\ContaoExtension;
use Contao\CoreBundle\Twig\Interop\PhpTemplateProxyNode;
use Twig\Compiler;
use Twig\Environment;

class PhpTemplateProxyNodeTest extends TestCase
{
    public function testCompilesProxyCode(): void
    {
        $compiler = new Compiler($this->createMock(Environment::class));

        (new PhpTemplateProxyNode(ContaoExtension::class))->compile($compiler);

        $expectedSource = <<<'SOURCE'
            yield $this->extensions["Contao\\CoreBundle\\Twig\\Extension\\ContaoExtension"]->renderLegacyTemplate(
                $this->getTemplateName(),
                array_map(
                    function(callable $block) use ($context): string {
                        if ($this->env->isDebug()) { ob_start(); } else { ob_start(static function () { return ''; }); }
                        try {
                            $content = '';
                            foreach ($block($context) ?? [''] as $chunk) {
                                $content .= ob_get_contents() . $chunk;
                                ob_clean();
                            }
                            return $content . ob_get_contents();
                        } finally { ob_end_clean(); }
                    }, array_intersect_key($blocks, $this->blocks)
                ), $context
            );

            SOURCE;

        $this->assertSame($expectedSource, $compiler->getSource());
    }
}
