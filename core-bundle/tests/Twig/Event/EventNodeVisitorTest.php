<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Event;

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Event\CompileTemplateEvent;
use Contao\CoreBundle\Twig\Event\EventsNodeVisitor;
use Contao\CoreBundle\Twig\Event\RenderEventNode;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Twig\Environment;
use Twig\Node\BodyNode;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\ModuleNode;
use Twig\Node\Node;
use Twig\Node\PrintNode;
use Twig\NodeTraverser;
use Twig\Source;

class EventNodeVisitorTest extends TestCase
{
    public function testPriority(): void
    {
        $visitor = new EventsNodeVisitor($this->createMock(EventDispatcherInterface::class));

        $this->assertSame(10, $visitor->getPriority());
    }

    public function testConfiguresTemplateProxy(): void
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(
                function (CompileTemplateEvent $event): CompileTemplateEvent {
                    $this->assertSame('@Contao_Foo/foo.html5', $event->getName());
                    $event->prepend('Hello ');

                    return $event;
                }
            )
        ;

        $visitor = new EventsNodeVisitor($eventDispatcher);

        $module = new ModuleNode(
            new BodyNode(),
            null,
            new Node(),
            new Node(),
            new Node(),
            null,
            new Source('world!', '@Contao_Foo/foo.html5')
        );

        $existingNode = new PrintNode(new ConstantExpression('existing', 1), 1);
        $module->setNode('display_start', new Node([$existingNode]));

        $environment = $this->createMock(Environment::class);
        (new NodeTraverser($environment, [$visitor]))->traverse($module);

        /** @var array<Node> $displayStart */
        $displayStart = iterator_to_array($module->getNode('display_start'));

        $this->assertInstanceOf(RenderEventNode::class, $displayStart[0]);
        $this->assertSame('Hello ', $displayStart[1]->getNode('expr')->getAttribute('value'));
        $this->assertSame($existingNode, $displayStart[2]);
    }
}
