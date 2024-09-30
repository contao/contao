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

use Contao\CoreBundle\Twig\ContaoTwigUtil;
use Twig\Environment;
use Twig\Node\BlockNode;
use Twig\Node\ModuleNode;
use Twig\Node\Node;
use Twig\NodeVisitor\NodeVisitorInterface;

/**
 * @experimental
 */
final class PhpTemplateProxyNodeVisitor implements NodeVisitorInterface
{
    public function __construct(private readonly string $extensionName)
    {
    }

    public function getPriority(): int
    {
        return 0;
    }

    public function enterNode(Node $node, Environment $env): Node
    {
        return $node;
    }

    public function leaveNode(Node $node, Environment $env): Node
    {
        if ($node instanceof ModuleNode && ContaoTwigUtil::isLegacyTemplate($node->getTemplateName() ?? '')) {
            $this->configurePhpTemplateProxy($node);
        }

        return $node;
    }

    /**
     * We are replacing the module body with a PhpTemplateProxyNode that will delegate
     * rendering to the Contao framework on the fly. To support blocks we're also
     * injecting a BlockNode for each block in the original source that will return
     * the default Contao block placeholder when called.
     */
    private function configurePhpTemplateProxy(ModuleNode $node): void
    {
        $blockNodes = [];

        foreach (explode("\n", $node->getSourceContext()->getCode()) as $name) {
            // Sanity check for valid block names
            if (1 !== preg_match('/^[a-z0-9_-]+$/i', $name)) {
                continue;
            }

            $blockNodes[$name] = new BlockNode($name, new PhpTemplateParentReferenceNode(), 0);
        }

        $node->setNode('blocks', new Node($blockNodes));
        $node->setNode('body', new PhpTemplateProxyNode($this->extensionName));
    }
}
