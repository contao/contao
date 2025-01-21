<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Twig\Inspector;

use Contao\CoreBundle\Twig\Inheritance\RuntimeThemeDependentExpression;
use Contao\CoreBundle\Twig\Slots\SlotNode;
use Psr\Cache\CacheItemPoolInterface;
use Twig\Environment;
use Twig\Node\BlockNode;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\ParentExpression;
use Twig\Node\ModuleNode;
use Twig\Node\Node;
use Twig\Node\PrintNode;
use Twig\NodeVisitor\NodeVisitorInterface;
use Twig\Source;
use Twig\Token;

/**
 * @experimental
 */
final class InspectorNodeVisitor implements NodeVisitorInterface
{
    /**
     * @var list<string>
     */
    private array $slots = [];

    private array $blocks = [];

    /**
     * @var \WeakMap<Source, list<string>>
     */
    private \WeakMap $prototypeBlocks;

    public function __construct(
        private readonly CacheItemPoolInterface $cachePool,
        private readonly Environment $twig,
    ) {
        $this->prototypeBlocks = new \WeakMap();
    }

    public function enterNode(Node $node, Environment $env): Node
    {
        if ($node instanceof SlotNode) {
            $this->slots[] = $node->getAttribute('name');
        } elseif ($node instanceof BlockNode) {
            $this->blocks[$node->getAttribute('name')] = [false, $this->isPrototype($node)];
        } elseif ($node instanceof PrintNode && $node->getNode('expr') instanceof ParentExpression) {
            $this->blocks[array_key_last($this->blocks)][0] = true;
        }

        return $node;
    }

    public function leaveNode(Node $node, Environment $env): Node
    {
        if (!$node instanceof ModuleNode) {
            return $node;
        }

        // Retrieve the parent template
        $getParent = function (ModuleNode $node): string|null {
            if (!$node->hasNode('parent')) {
                return null;
            }

            return $this->getValue($node->getNode('parent'));
        };

        // Retrieve used templates
        $getUses = function (ModuleNode $node): array {
            if (!$node->hasNode('traits')) {
                return [];
            }

            $uses = [];

            foreach ($node->getNode('traits') as $trait) {
                if (null !== ($template = $this->getValue($trait->getNode('template')))) {
                    $targets = [];

                    foreach ($trait->getNode('targets') as $original => $target) {
                        $targets[$original] = $target->getAttribute('value');
                    }

                    $uses[] = [$template, $targets];
                }
            }

            return $uses;
        };

        $this->persist($node->getSourceContext()->getPath(), [
            'slots' => array_unique($this->slots),
            'blocks' => $this->blocks,
            'parent' => $getParent($node),
            'uses' => $getUses($node),
        ]);

        $this->slots = [];
        $this->blocks = [];

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

    private function persist(string $path, array $data): void
    {
        $item = $this->cachePool->getItem(Inspector::CACHE_KEY);

        $entries = $item->get() ?? [];
        $entries[$path] = $data;

        $item->set($entries);

        $this->cachePool->save($item);
    }

    private function isPrototype(BlockNode $block): bool
    {
        $source = $block->getSourceContext();
        $blockName = $block->getAttribute('name');

        if (null !== ($prototypeBlocks = ($this->prototypeBlocks[$source] ?? null))) {
            return \in_array($blockName, $prototypeBlocks, true);
        }

        $tokenStream = $this->twig->tokenize($source);
        $prototypeBlocks = [];

        while (!$tokenStream->isEOF()) {
            if (
                $tokenStream->nextIf(Token::BLOCK_START_TYPE)
                && $tokenStream->nextIf(Token::NAME_TYPE, 'block')
                && ($target = $tokenStream->nextIf(Token::NAME_TYPE))
                && $tokenStream->nextIf(Token::BLOCK_END_TYPE)
                && $tokenStream->nextIf(Token::BLOCK_START_TYPE)
                && $tokenStream->nextIf(Token::NAME_TYPE, 'endblock')
            ) {
                /** @var string $value */
                $value = $target->getValue();

                $prototypeBlocks[] = $value;
            }

            $tokenStream->next();
        }

        $this->prototypeBlocks[$source] = $prototypeBlocks;

        return \in_array($blockName, $prototypeBlocks, true);
    }

    private function getValue(Node $node): string|null
    {
        if ($node instanceof ConstantExpression) {
            return $node->getAttribute('value');
        }

        if ($node instanceof RuntimeThemeDependentExpression) {
            return $node->getAttribute('default_value');
        }

        return null;
    }
}
