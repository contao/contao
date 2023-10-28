<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Twig\Slots;

use Twig\Environment;
use Twig\Node\ModuleNode;
use Twig\Node\Node;
use Twig\NodeVisitor\NodeVisitorInterface;

/**
 * @experimental
 */
final class SetupSlotSystemNodeVisitor implements NodeVisitorInterface
{
    public function enterNode(Node $node, Environment $env): Node
    {
        return $node;
    }

    public function leaveNode(Node $node, Environment $env): Node|null
    {
        if ($node instanceof ModuleNode) {
            $this->setupSlotSystem($node);
        }

        return $node;
    }

    public function getPriority(): int
    {
        return 0;
    }

    private function setupSlotSystem(ModuleNode $node): void
    {
        $node->getNode('class_end')->setNode('slot_property', new SlotPropertyNode());
        $node->getNode('display_start')->setNode('slot_property_assign', new SlotPropertyAssignNode());
    }
}
