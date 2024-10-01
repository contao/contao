<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Extension;

use Twig\Environment;
use Twig\Node\DeprecatedNode;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Node;
use Twig\Node\PrintNode;
use Twig\NodeVisitor\NodeVisitorInterface;

/**
 * @internal
 */
class DeprecationsNodeVisitor implements NodeVisitorInterface
{
    public function getPriority(): int
    {
        return 10;
    }

    public function enterNode(Node $node, Environment $env): Node
    {
        return $node;
    }

    public function leaveNode(Node $node, Environment $env): Node
    {
        return $this->handleDeprecatedInsertTagUsage($node);
    }

    /**
     * Discourage the use of insert tags as strings like "{{ '{{link_url::9}}' }}"
     * instead of directly evaluating them via the "insert_tag" function.
     */
    private function handleDeprecatedInsertTagUsage(Node $node): Node
    {
        if (!$node instanceof PrintNode) {
            return $node;
        }

        $expression = $node->getNode('expr');

        if (!$expression instanceof ConstantExpression) {
            return $node;
        }

        if (1 !== preg_match('/{{([^}]+)}}/', (string) $expression->getAttribute('value'), $matches)) {
            return $node;
        }

        $suggestedTransformation = \sprintf('"{{ \'{{%1$s}}\' }}" -> "{{ insert_tag(\'%1$s\') }}".', $matches[1]);

        $message = 'You should not rely on insert tags being replaced in the rendered HTML. '
            .'This behavior will gradually be phased out in Contao 5 and will no longer work in Contao 6. '
            .'Explicitly replace insert tags with the "insert_tag" function instead: '.$suggestedTransformation;

        return $this->addDeprecation($node, $message);
    }

    private function addDeprecation(Node $node, string $message): Node
    {
        $line = $node->getTemplateLine();

        /** @phpstan-ignore arguments.count */
        $deprecatedNode = new DeprecatedNode(
            new ConstantExpression("Since contao/core-bundle 4.13: $message", $line),
            $line,
            $node->getNodeTag(),
        );

        // Set the source context, so that the template name can be inserted when
        // compiling the DeprecatedNode.
        $deprecatedNode->setSourceContext($node->getSourceContext());

        return new Node([$node, $deprecatedNode]);
    }
}
