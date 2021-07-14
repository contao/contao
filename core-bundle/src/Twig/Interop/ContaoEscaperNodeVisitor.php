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

/**
 * This NodeVisitor alters all "escape('html')" filter expressions into
 * "escape('contao_html')" filter expressions if the template they belong
 * to is amongst the configured affected templates.
 *
 * @experimental
 */
final class ContaoEscaperNodeVisitor extends AbstractNodeVisitor
{
    /**
     * We evaluate affected templates on the fly so that rules can be adjusted
     * after building the container. Expects a list of regular expressions to
     * be returned. A template counts as 'affected' if it matches any of the
     * rules.
     *
     * @var \Closure():array<string>
     */
    private $rules;

    /**
     * @var array|null
     */
    private $escaperFilterNodes;

    public function __construct(\Closure $rules)
    {
        $this->rules = $rules;
    }

    /**
     * Make sure to run after @see \Twig\NodeVisitor\EscaperNodeVisitor.
     */
    public function getPriority(): int
    {
        return 1;
    }

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
        } elseif (null !== $this->escaperFilterNodes && $this->isEscaperFilterExpressionWithHtmlStrategy($node)) {
            $this->escaperFilterNodes[] = $node;
        }

        return $node;
    }

    protected function doLeaveNode(Node $node, Environment $env)
    {
        if ($node instanceof ModuleNode && null !== $this->escaperFilterNodes) {
            foreach ($this->escaperFilterNodes as $escaperFilterNode) {
                $this->setContaoEscaperArguments($escaperFilterNode);
            }

            $this->escaperFilterNodes = null;
        }

        return $node;
    }

    private function isEscaperFilterExpressionWithHtmlStrategy(Node $node): bool
    {
        return $node instanceof FilterExpression
            && 'escape' === $node->getNode('filter')->getAttribute('value')
            && $node->getNode('arguments')->hasNode(0)
            && ($argument = $node->getNode('arguments')->getNode(0)) instanceof ConstantExpression
            && 'html' === $argument->getAttribute('value');
    }

    private function setContaoEscaperArguments(FilterExpression $node): void
    {
        $line = $node->getTemplateLine();

        $arguments = new Node([
            new ConstantExpression('contao_html', $line),
            new ConstantExpression(null, $line),
            new ConstantExpression(true, $line),
        ]);

        $node->setNode('arguments', $arguments);
    }
}
