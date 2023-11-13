<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Twig\Inspector;

use Contao\CoreBundle\Twig\Slots\SlotNode;
use Psr\Cache\CacheItemPoolInterface;
use Twig\Environment;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\ModuleNode;
use Twig\Node\Node;
use Twig\NodeVisitor\NodeVisitorInterface;

/**
 * @experimental
 */
final class InspectorNodeVisitor implements NodeVisitorInterface
{
    /**
     * @var list<string>
     */
    private array $slots = [];

    public function __construct(private readonly CacheItemPoolInterface $cachePool)
    {
    }

    public function enterNode(Node $node, Environment $env): Node
    {
        if ($node instanceof SlotNode) {
            $this->slots[] = $node->getAttribute('name');
        }

        return $node;
    }

    public function leaveNode(Node $node, Environment $env): Node|null
    {
        if (!$node instanceof ModuleNode) {
            return $node;
        }

        // Retrieve the parent template if it was set statically
        $getParent = static function (ModuleNode $node): string|null {
            if (!$node->hasNode('parent') || !($parent = $node->getNode('parent')) instanceof ConstantExpression) {
                return null;
            }

            return $parent->getAttribute('value');
        };

        $this->persist($node->getTemplateName(), [
            'slots' => array_unique($this->slots),
            'parent' => $getParent($node),
        ]);

        $this->slots = [];

        return $node;
    }

    /**
     * Run late to capture the correct state but before the OptimizerNodeVisitor.
     *
     * @see \Twig\NodeVisitor\OptimizerNodeVisitor
     */
    public function getPriority(): int
    {
        return 128;
    }

    private function persist(string $templateName, array $data): void
    {
        $item = $this->cachePool->getItem(Inspector::CACHE_KEY);

        $entries = $item->get() ?? [];
        $entries[$templateName] = $data;

        $item->set($entries);

        $this->cachePool->save($item);
    }
}
