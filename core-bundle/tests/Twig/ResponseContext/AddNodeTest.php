<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\ResponseContext;

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Extension\ContaoExtension;
use Contao\CoreBundle\Twig\ResponseContext\AddNode;
use Contao\CoreBundle\Twig\ResponseContext\DocumentLocation;
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Environment;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\PrintNode;

class AddNodeTest extends TestCase
{
    public function testCompilesAddNode(): void
    {
        $addNode = new AddNode(
            ContaoExtension::class,
            new PrintNode(new ConstantExpression('foobar', 42), 42),
            'identifier',
            DocumentLocation::endOfBody,
            1,
        );

        $compiler = new Compiler($this->createMock(Environment::class));
        $compiler->compile($addNode);

        $expectedSource = <<<'SOURCE'
            if ($this->env->isDebug()) { ob_start(); } else { ob_start(static function () { return ''; }); }
            try {
                $__contao_document_content = '';
                foreach((function () use (&$context, $macros, $blocks) {
                    // line 42
                    echo "foobar";
                    yield '';
                })() as $__contao_document_chunk) {
                    $__contao_document_content .= ob_get_contents() . $__contao_document_chunk;
                    ob_clean();
                }
            } finally { ob_end_clean(); }
            $this->extensions["Contao\\CoreBundle\\Twig\\Extension\\ContaoExtension"]->addDocumentContent(
                "identifier", $__contao_document_content, \Contao\CoreBundle\Twig\ResponseContext\DocumentLocation::endOfBody
            );

            SOURCE;

        if (class_exists(YieldReady::class)) {
            $expectedSource = str_replace('echo', 'yield', $expectedSource);
        }

        $this->assertSame($expectedSource, $compiler->getSource());
    }
}
