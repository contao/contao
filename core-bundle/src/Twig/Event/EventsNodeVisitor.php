<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Event;

use Contao\CoreBundle\Event\CompileTemplateEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Twig\Environment;
use Twig\Node\ModuleNode;
use Twig\Node\Node;
use Twig\NodeVisitor\AbstractNodeVisitor;

/**
 * @experimental
 */
final class EventsNodeVisitor extends AbstractNodeVisitor
{
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function getPriority(): int
    {
        return 10;
    }

    protected function doEnterNode(Node $node, Environment $env): Node
    {
        return $node;
    }

    protected function doLeaveNode(Node $node, Environment $env): Node
    {
        if ($node instanceof ModuleNode) {
            $this->addRenderEventNode($node);
            $this->dispatchCompileEvent($node);
        }

        return $node;
    }

    private function addRenderEventNode(ModuleNode $node): void
    {
        $node->setNode(
            'display_start',
            new Node([new RenderEventNode(), $node->getNode('display_start')])
        );
    }

    private function dispatchCompileEvent(ModuleNode $node): void
    {
        $event = new CompileTemplateEvent($node);

        $this->eventDispatcher->dispatch($event);
    }
}
