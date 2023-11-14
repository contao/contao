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

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Interop\PhpTemplateParentReferenceNode;
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
    #[\Override]
    protected function tearDown(): void
    {
        $this->resetStaticProperties([ContaoFramework::class]);

        parent::tearDown();
    }

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
            new Source("a\n<?php invalid block\nb", '@Contao_Foo/foo.html5'),
        );

        $framework = new \ReflectionClass(ContaoFramework::class);
        $framework->setStaticPropertyValue('nonce', '<nonce>');

        $environment = $this->createMock(Environment::class);
        (new NodeTraverser($environment, [$visitor]))->traverse($module);

        /** @var array<BlockNode> $blocks */
        $blocks = iterator_to_array($module->getNode('blocks'));

        $this->assertCount(2, $blocks, 'invalid block names should be ignored');

        $this->assertSame('a', $blocks['a']->getAttribute('name'));
        $this->assertSame('b', $blocks['b']->getAttribute('name'));

        $this->assertInstanceOf(PhpTemplateParentReferenceNode::class, $blocks['a']->getNode('body'));
        $this->assertInstanceOf(PhpTemplateParentReferenceNode::class, $blocks['b']->getNode('body'));

        $this->assertInstanceOf(PhpTemplateProxyNode::class, $module->getNode('body'));
    }
}
