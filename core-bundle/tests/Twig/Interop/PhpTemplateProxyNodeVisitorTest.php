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
use Contao\CoreBundle\Twig\Interop\PhpTemplateProxyNode;
use Contao\CoreBundle\Twig\Interop\PhpTemplateProxyNodeVisitor;
use Twig\Environment;
use Twig\Node\BlockNode;
use Twig\Node\BodyNode;
use Twig\Node\ModuleNode;
use Twig\Node\Node;
use Twig\NodeTraverser;
use Twig\Source;

class PhpTemplateProxyNodeVisitorTest extends TestCase
{
    public function testPriority(): void
    {
        $visitor = new PhpTemplateProxyNodeVisitor('ExtensionClass');

        $this->assertSame(0, $visitor->getPriority());
    }

    public function testConfiguresTemplateProxy(): void
    {
        $visitor = new PhpTemplateProxyNodeVisitor('ExtensionClass');

        $module = new ModuleNode(
            new BodyNode(),
            null,
            new Node(),
            new Node(),
            new Node(),
            null,
            new Source("a\nb", 'foo.html5')
        );

        $environment = $this->createMock(Environment::class);
        (new NodeTraverser($environment, [$visitor]))->traverse($module);

        /** @var array<BlockNode> $blocks */
        $blocks = iterator_to_array($module->getNode('blocks'));

        $this->assertCount(2, $blocks);

        $this->assertSame('a', $blocks['a']->getAttribute('name'));
        $this->assertSame('b', $blocks['b']->getAttribute('name'));

        $this->assertSame('[[TL_PARENT]]', $blocks['a']->getNode('body')->getAttribute('data'));
        $this->assertSame('[[TL_PARENT]]', $blocks['b']->getNode('body')->getAttribute('data'));

        $this->assertInstanceOf(PhpTemplateProxyNode::class, $module->getNode('body'));
    }
}
