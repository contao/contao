<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Event;

use Contao\CoreBundle\Twig\Event\TemplateNameTrait;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\ModuleNode;
use Twig\Node\Node;
use Twig\Node\PrintNode;

/**
 * @experimental
 */
class CompileTemplateEvent
{
    use TemplateNameTrait;

    private ModuleNode $node;

    /**
     * @internal
     */
    public function __construct(ModuleNode $node)
    {
        $this->node = $node;

        $sourceContext = $node->getSourceContext();
        $this->name = null !== $sourceContext ? $sourceContext->getName() : '';
    }

    /**
     * Add raw content that will be output before the template body.
     */
    public function prepend(string $content): void
    {
        $this->node->setNode(
            'display_start',
            new Node([
                $this->node->getNode('display_start'),
                new PrintNode(new ConstantExpression($content, 0), 0),
            ])
        );
    }

    /**
     * Add raw content that will be output after the template body.
     */
    public function append(string $content): void
    {
        $this->node->setNode(
            'display_end',
            new Node([
                new PrintNode(new ConstantExpression($content, 0), 0),
                $this->node->getNode('display_end'),
            ])
        );
    }
}
