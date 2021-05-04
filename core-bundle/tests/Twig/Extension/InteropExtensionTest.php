<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Extension;

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Extension\InteropExtension;
use Contao\CoreBundle\Twig\Interop\ContaoEscaperNodeVisitor;
use Twig\Environment;
use Twig\Extension\EscaperExtension;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\FilterExpression;
use Twig\Node\ModuleNode;
use Twig\Node\Node;
use Twig\Node\TextNode;
use Twig\NodeTraverser;
use Twig\Source;

class InteropExtensionTest extends TestCase
{
    public function testAddsTheContaoEscaperNodeVisitor(): void
    {
        $environment = $this->createMock(Environment::class);
        $environment
            ->method('getExtension')
            ->with(EscaperExtension::class)
            ->willReturn(new EscaperExtension())
        ;

        $nodeVisitors = $this->getInteropExtension()->getNodeVisitors();

        $this->assertCount(1, $nodeVisitors);
        $this->assertInstanceOf(ContaoEscaperNodeVisitor::class, $nodeVisitors[0]);
    }

    public function testAllowsOnTheFlyRegisteringTemplatesForInputEncoding(): void
    {
        $interopExtension = $this->getInteropExtension();

        $escaperNodeVisitor = $interopExtension->getNodeVisitors()[0];

        $traverser = new NodeTraverser(
            $this->createMock(Environment::class),
            [$escaperNodeVisitor]
        );

        $node = new ModuleNode(
            new FilterExpression(
                new TextNode('text', 1),
                new ConstantExpression('escape', 1),
                new Node([
                    new ConstantExpression('html', 1),
                    new ConstantExpression(null, 1),
                    new ConstantExpression(true, 1),
                ]),
                1
            ),
            null,
            new Node(),
            new Node(),
            new Node(),
            null,
            new Source('<code>', 'foo.html.twig')
        );

        $original = $node->__toString();

        // Traverse tree first time (no changes expected)
        $traverser->traverse($node);
        $iteration1 = $node->__toString();

        // Register a template and traverse tree a second time (change expected)
        $interopExtension->registerTemplateForInputEncoding('foo.html.twig');
        $traverser->traverse($node);
        $iteration2 = $node->__toString();

        $this->assertSame($original, $iteration1);
        $this->assertStringNotContainsString("'contao_html'", $iteration1);
        $this->assertStringContainsString("'contao_html'", $iteration2);
    }

    private function getInteropExtension(): InteropExtension
    {
        $environment = $this->createMock(Environment::class);
        $environment
            ->method('getExtension')
            ->with(EscaperExtension::class)
            ->willReturn(new EscaperExtension())
        ;

        return new InteropExtension($environment);
    }
}
