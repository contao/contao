<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Event;

use Contao\CoreBundle\Event\CompileTemplateEvent;
use Contao\CoreBundle\Tests\TestCase;
use Twig\Node\BodyNode;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\NameExpression;
use Twig\Node\IncludeNode;
use Twig\Node\ModuleNode;
use Twig\Node\Node;
use Twig\Node\PrintNode;
use Twig\Source;

class CompileTemplateEventTest extends TestCase
{
    public function testAddsPrintNodes(): void
    {
        $module = $this->getModuleNode();
        $event = new CompileTemplateEvent($module);

        $event->prepend('<span>');
        $event->append('</span>');
        $event->prepend('<div class="foo">');
        $event->append('</div>');

        /** @var array<Node> $displayStart */
        $displayStart = iterator_to_array($module->getNode('display_start'));

        /** @var array<Node> $displayEnd */
        $displayEnd = iterator_to_array($module->getNode('display_end'));

        $this->assertInstanceOf(PrintNode::class, $displayStart[0]);
        $this->assertInstanceOf(PrintNode::class, $displayStart[1]);
        $this->assertInstanceOf(PrintNode::class, $displayEnd[0]);
        $this->assertInstanceOf(PrintNode::class, $displayEnd[1]);

        $this->assertExpressionWithValue($displayStart[0], '<div class="foo">');
        $this->assertExpressionWithValue($displayStart[1], '<span>');
        $this->assertExpressionWithValue($displayEnd[0], '</span>');
        $this->assertExpressionWithValue($displayEnd[1], '</div>');
    }

    public function testAddsIncludeNodes(): void
    {
        $module = $this->getModuleNode();
        $event = new CompileTemplateEvent($module);

        $event->prependInclude('before1.html.twig');
        $event->appendInclude('after1.html.twig');
        $event->prependInclude('before2.html.twig', ['foo', 'bar']);
        $event->appendInclude('after2.html.twig', ['foo' => 'foobar']);

        /** @var array<Node> $displayStart */
        $displayStart = iterator_to_array($module->getNode('display_start'));

        /** @var array<Node> $displayEnd */
        $displayEnd = iterator_to_array($module->getNode('display_end'));

        $this->assertInstanceOf(IncludeNode::class, $displayStart[0]);
        $this->assertInstanceOf(IncludeNode::class, $displayStart[1]);
        $this->assertInstanceOf(IncludeNode::class, $displayEnd[0]);
        $this->assertInstanceOf(IncludeNode::class, $displayEnd[1]);

        $this->assertExpressionWithValue($displayStart[0], 'before2.html.twig');
        $this->assertNodeWithVariables($displayStart[0], ['foo' => 'foo', 'bar' => 'bar']);
        $this->assertTrue($displayStart[0]->getAttribute('only'));

        $this->assertExpressionWithValue($displayStart[1], 'before1.html.twig');
        $this->assertFalse($displayStart[1]->hasNode('variables'));
        $this->assertFalse($displayStart[1]->getAttribute('only'));

        $this->assertExpressionWithValue($displayEnd[0], 'after1.html.twig');
        $this->assertFalse($displayEnd[0]->hasNode('variables'));
        $this->assertFalse($displayEnd[0]->getAttribute('only'));

        $this->assertExpressionWithValue($displayEnd[1], 'after2.html.twig');
        $this->assertNodeWithVariables($displayEnd[1], ['foo' => 'foobar']);
        $this->assertTrue($displayEnd[1]->getAttribute('only'));
    }

    /**
     * @dataProvider provideIncludeMethods
     */
    public function testThrowsWhenTryingToIncludeTheSameTemplate(string $method): void
    {
        $event = new CompileTemplateEvent($this->getModuleNode());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('You cannot append the template \'source.html.twig\' to itself as this would cause infinite recursion.');

        $event->$method('source.html.twig');
    }

    public function provideIncludeMethods(): \Generator
    {
        yield 'prepend' => ['prependInclude'];
        yield 'append' => ['appendInclude'];
    }

    private function assertExpressionWithValue(Node $node, string $value): void
    {
        $this->assertSame($value, $node->getNode('expr')->getAttribute('value'));
    }

    private function assertNodeWithVariables(Node $node, array $variableDefinitions): void
    {
        /** @var array<int, Node> $variables */
        $variables = iterator_to_array($node->getNode('variables'));
        $this->assertCount(2 * \count($variableDefinitions), $variables);

        $i = 0;

        foreach ($variableDefinitions as $variableName => $declaredAs) {
            $this->assertInstanceOf(ConstantExpression::class, $variables[$i]);
            $this->assertSame($declaredAs, $variables[$i]->getAttribute('value'));

            $this->assertInstanceOf(NameExpression::class, $variables[$i + 1]);
            $this->assertSame($variableName, $variables[$i + 1]->getAttribute('name'));

            $i += 2;
        }
    }

    private function getModuleNode(): ModuleNode
    {
        return new ModuleNode(
            new BodyNode(),
            null,
            new Node(),
            new Node(),
            new Node(),
            [],
            new Source('source', 'source.html.twig')
        );
    }
}
