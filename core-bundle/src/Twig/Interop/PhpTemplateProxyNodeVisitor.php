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
use Twig\Node\BlockNode;
use Twig\Node\ModuleNode;
use Twig\Node\Node;
use Twig\Node\TextNode;
use Twig\NodeVisitor\AbstractNodeVisitor;
use Webmozart\PathUtil\Path;

/**
 * @experimental
 */
final class PhpTemplateProxyNodeVisitor extends AbstractNodeVisitor
{
    /**
     * @var string
     */
    private $extensionName;

    public function __construct(string $extensionName)
    {
        $this->extensionName = $extensionName;
    }

    public function getPriority(): int
    {
        return 0;
    }

    protected function doEnterNode(Node $node, Environment $env): Node
    {
        return $node;
    }

    protected function doLeaveNode(Node $node, Environment $env): Node
    {
        if ($node instanceof ModuleNode && 'html5' === Path::getExtension($node->getTemplateName(), true)) {
            $this->configurePhpTemplateProxy($node);
        }

        return $node;
    }

    /**
     * We are replacing the module body with a PhpTemplateProxyNode that will
     * delegate rendering to the Contao framework on the fly. To support blocks
     * we're also injecting a BlockNode for each block in the original source
     * that will return the default Contao block placeholder when called.
     */
    private function configurePhpTemplateProxy(ModuleNode $node): void
    {
        $blockNodes = [];

        foreach (explode("\n", $node->getSourceContext()->getCode()) as $name) {
            $blockNodes[$name] = new BlockNode($name, new TextNode('[[TL_PARENT]]', 0), 0);
        }

        $node->setNode('blocks', new Node($blockNodes));
        $node->setNode('body', new PhpTemplateProxyNode($this->extensionName));
    }
}
