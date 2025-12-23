<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Twig\Inspector;

use Contao\CoreBundle\Twig\Inheritance\RuntimeThemeDependentExpression;
use Contao\CoreBundle\Twig\Slots\SlotNode;
use Twig\Environment;
use Twig\Node\BlockNode;
use Twig\Node\BlockReferenceNode;
use Twig\Node\Expression\BlockReferenceExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\FilterExpression;
use Twig\Node\Expression\FunctionExpression;
use Twig\Node\Expression\ParentExpression;
use Twig\Node\ModuleNode;
use Twig\Node\Node;
use Twig\Node\PrintNode;
use Twig\NodeVisitor\NodeVisitorInterface;
use Twig\Source;
use Twig\Token;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * @experimental
 */
final class InspectorNodeVisitor implements NodeVisitorInterface
{
    /**
     * @var array<string, string|null>
     */
    private array $slots = [];

    /**
     * Mapping of found block names (keys) to their usage properties (values) in the
     * form of [0 => <uses parent function>, 1 => <is a prototype block>].
     *
     * @var array<string, array{0: bool, 1: bool}>
     */
    private array $blocks = [];

    /**
     * List of found blocks that are output via a block reference expressions.
     *
     * @var list<string>
     */
    private array $calledBlocks = [];

    /**
     * @var \WeakMap<Source, list<string>>
     */
    private \WeakMap $prototypeBlocks;

    /**
     * @var array<string, string>
     */
    private array $blockNesting = [];

    private string|null $currentBlock = null;

    /**
     * @var list<string>|null
     */
    private array|null $deprecatedFunctions = null;

    /**
     * @var list<string>|null
     */
    private array|null $deprecatedFilters = null;

    /**
     * @var list<array{line: int, message: string}>
     */
    private array $deprecations = [];

    public function __construct(
        private readonly Storage $storage,
        private readonly Environment $twig,
    ) {
        $this->prototypeBlocks = new \WeakMap();
    }

    public function enterNode(Node $node, Environment $env): Node
    {
        if ($node instanceof ModuleNode) {
            $this->init();
        } elseif ($node instanceof SlotNode) {
            $this->slots[$node->getAttribute('name')] = $this->currentBlock;
        } elseif ($node instanceof BlockNode) {
            $name = $node->getAttribute('name');
            $this->currentBlock = $name;
            $this->blocks[$name] = [false, $this->isPrototype($node)];
        } elseif ($node instanceof BlockReferenceNode) {
            $this->blockNesting[$node->getAttribute('name')] = $this->currentBlock;
        } elseif ($node instanceof PrintNode) {
            $expression = $node->getNode('expr');

            if ($expression instanceof ParentExpression) {
                $this->blocks[array_key_last($this->blocks)][0] = true;
            } elseif ($expression instanceof BlockReferenceExpression && null !== ($name = $this->getValue($expression->getNode('name')))) {
                $this->calledBlocks[] = $name;
            }
        } elseif ($node instanceof FunctionExpression && \in_array($name = $node->getAttribute('name'), $this->deprecatedFunctions, true)) {
            $this->deprecations[] = [
                'line' => $node->getTemplateLine(),
                'message' => $this->captureDeprecation(fn () => $this->twig->getFunction($name)->triggerDeprecation()),
            ];
        } elseif ($node instanceof FilterExpression && \in_array($name = $node->getAttribute('name'), $this->deprecatedFilters, true)) {
            $this->deprecations[] = [
                'line' => $node->getTemplateLine(),
                'message' => $this->captureDeprecation(fn () => $this->twig->getFilter($name)->triggerDeprecation()),
            ];
        }

        return $node;
    }

    public function leaveNode(Node $node, Environment $env): Node
    {
        if ($node instanceof BlockNode) {
            $this->currentBlock = null;
        }

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

        $this->storage->set($node->getSourceContext()->getPath(), [
            'slots' => $this->slots,
            'blocks' => $this->blocks,
            'nesting' => $this->blockNesting,
            'calls' => $this->calledBlocks,
            'parent' => $getParent($node),
            'uses' => $getUses($node),
            'deprecations' => $this->deprecations,
        ]);

        $this->slots = [];
        $this->blocks = [];
        $this->blockNesting = [];
        $this->calledBlocks = [];

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

    private function init(): void
    {
        if (null !== $this->deprecatedFunctions) {
            return;
        }

        $this->deprecatedFunctions = array_values(
            array_map(
                static fn (TwigFunction $function): string => $function->getName(),
                array_filter(
                    $this->twig->getFunctions(),
                    static fn (TwigFunction $function): bool => $function->isDeprecated(),
                ),
            ),
        );

        $this->deprecatedFilters = array_values(
            array_map(
                static fn (TwigFilter $filter): string => $filter->getName(),
                array_filter(
                    $this->twig->getFilters(),
                    static fn (TwigFilter $filter): bool => $filter->isDeprecated(),
                ),
            ),
        );
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

    private function captureDeprecation(callable $callable): string
    {
        $message = '';

        set_error_handler(
            static function ($type, $msg) use (&$message) {
                if (E_USER_DEPRECATED === $type) {
                    $message = $msg;
                }

                return false;
            },
        );

        $callable();

        restore_error_handler();

        return $message;
    }
}
