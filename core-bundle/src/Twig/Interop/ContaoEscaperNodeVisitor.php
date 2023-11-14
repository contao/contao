<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Interop;

use Twig\Environment;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\FilterExpression;
use Twig\Node\ModuleNode;
use Twig\Node\Node;
use Twig\NodeVisitor\AbstractNodeVisitor;
use Twig\NodeVisitor\EscaperNodeVisitor;

/**
 * This NodeVisitor alters all "escape('html')" and "escape('html_attr')"
 * filter expressions into "escape('contao_html')" and
 * "escape('contao_html_attr')" filter expressions if the template they belong
 * to is amongst the configured affected templates.
 *
 * @experimental
 */
final class ContaoEscaperNodeVisitor extends AbstractNodeVisitor
{
    private array|null $escaperFilterNodes = null;

    public function __construct(
        /**
         * We evaluate affected templates on the fly so that rules can be
         * adjusted after building the container. Expects a list of regular
         * expressions to be returned. A template counts as "affected" if it
         * matches any of the rules.
         */
        private readonly \Closure $rules,
    ) {
    }

    /**
     * Make sure to run after the EscaperNodeVisitor.
     *
     * @see EscaperNodeVisitor
     */
    #[\Override]
    public function getPriority(): int
    {
        return 1;
    }

    #[\Override]
    protected function doEnterNode(Node $node, Environment $env): Node
    {
        $isAffected = static function (array $rules, string $name): bool {
            foreach ($rules as $rule) {
                if (1 === preg_match($rule, $name)) {
                    return true;
                }
            }

            return false;
        };

        if ($node instanceof ModuleNode && $isAffected(($this->rules)(), $node->getTemplateName() ?? '')) {
            $this->escaperFilterNodes = [];
        } elseif (
            null !== $this->escaperFilterNodes && $this->isEscaperFilterExpression($node, $strategy)
            && \in_array($strategy, ['html', 'html_attr'], true)
        ) {
            $this->escaperFilterNodes[] = [$node, $strategy];
        }

        return $node;
    }

    #[\Override]
    protected function doLeaveNode(Node $node, Environment $env): Node|null
    {
        if ($node instanceof ModuleNode && null !== $this->escaperFilterNodes) {
            foreach ($this->escaperFilterNodes as [$escaperFilterNode, $strategy]) {
                $this->setContaoEscaperArguments($escaperFilterNode, $strategy);
            }

            $this->escaperFilterNodes = null;
        }

        return $node;
    }

    /**
     * @param-out string $type
     */
    private function isEscaperFilterExpression(Node $node, string|null &$type = null): bool
    {
        $type = '';

        if (!$node instanceof FilterExpression || !$node->getNode('arguments')->hasNode('0')) {
            return false;
        }

        $argument = $node->getNode('arguments')->getNode('0');

        if (!$argument instanceof ConstantExpression) {
            return false;
        }

        if (!\in_array($node->getNode('filter')->getAttribute('value'), ['escape', 'e'], true)) {
            return false;
        }

        $type = $argument->getAttribute('value');

        return true;
    }

    private function setContaoEscaperArguments(FilterExpression $node, string $strategy): void
    {
        $line = $node->getTemplateLine();

        $arguments = new Node([
            new ConstantExpression("contao_$strategy", $line),
            new ConstantExpression(null, $line),
            new ConstantExpression(true, $line),
        ]);

        $node->setNode('arguments', $arguments);
    }
}
