<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Slots;

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Slots\SetupSlotSystemNodeVisitor;
use Contao\CoreBundle\Twig\Slots\SlotPropertyAssignNode;
use Contao\CoreBundle\Twig\Slots\SlotPropertyNode;
use Twig\Environment;
use Twig\Node\BodyNode;
use Twig\Node\ModuleNode;
use Twig\Node\Node;
use Twig\NodeTraverser;
use Twig\Source;

class SetupSlotSystemVisitorTest extends TestCase
{
    public function testHashDefaultPriority(): void
    {
        $this->assertSame(0, (new SetupSlotSystemNodeVisitor())->getPriority());
    }

    public function testInjectsSlotNodes(): void
    {
        $visitor = new SetupSlotSystemNodeVisitor();

        $module = new ModuleNode(
            new BodyNode(),
            null,
            new Node(),
            new Node(),
            new Node(),
            null,
            new Source('[…]', 'foo.html.twig'),
        );

        $environment = $this->createMock(Environment::class);
        (new NodeTraverser($environment, [$visitor]))->traverse($module);

        $this->assertInstanceOf(SlotPropertyNode::class, $module->getNode('class_end')->getNode('slot_property'));
        $this->assertInstanceOf(SlotPropertyAssignNode::class, $module->getNode('display_start')->getNode('slot_property_assign'));
    }
}
