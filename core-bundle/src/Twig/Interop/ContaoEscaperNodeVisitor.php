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
 */
class ContaoEscaperNodeVisitor extends AbstractNodeVisitor
{
    // todo: handle filters, e.g. {{ foo|upper }} …

    /**
     * Evaluate affected templates on the fly so that they can be added after
     * building the container.
     *
     * @var \Closure():array<string>
     */
    private $affectedTemplates;

    /**
     * @var string|null
     */
    private $parentTemplate;

    /**
     * @var array|null
     */
    private $escaperFilterNodes;

    public function __construct(\Closure $affectedTemplates)
    {
        $this->affectedTemplates = $affectedTemplates;
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
        $isAffected = function (?string $name): bool {
            return \in_array($name, ($this->affectedTemplates)(), true) || $name === $this->parentTemplate;
        };

        if ($node instanceof ModuleNode && $isAffected($node->getTemplateName())) {
            $this->escaperFilterNodes = [];

            // Propagate escape strategy to parent template
            if ($node->hasNode('parent') && ($parent = $node->getNode('parent')) instanceof ConstantExpression) {
                $this->parentTemplate = $parent->getAttribute('value');
            } else {
                $this->parentTemplate = null;
            }
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
        if (!$node instanceof FilterExpression) {
            return false;
        }

        $getConstantValue = static function (Node $node): ?string {
            if (!$node instanceof ConstantExpression) {
                return null;
            }

            return $node->getAttribute('value');
        };

        return 'escape' === $getConstantValue($node->getNode('filter')) &&
            'html' === $getConstantValue($node->getNode('arguments')->getNode(0));
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
