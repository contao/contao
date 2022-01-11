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
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\NameExpression;
use Twig\Node\IncludeNode;
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

        if (null !== $sourceContext = $node->getSourceContext()) {
            $this->setName($sourceContext->getName());
        }
    }

    /**
     * Add raw content that will be output at the beginning of the template.
     */
    public function prepend(string $content): void
    {
        $this->prependNode($this->getPrintNode($content));
    }

    /**
     * Add raw content that will be output at the end of the template.
     */
    public function append(string $content): void
    {
        $this->appendNode($this->getPrintNode($content));
    }

    /**
     * Include another template ({% include 'template' %}) at the beginning of
     * the template.
     *
     * You can optionally define how the template's context should be passed
     * on to the included template by providing a $with mapping.
     *
     *   - Pass the full context on to the included template:
     *      prependInclude("@Contao/_my_include.html.twig")
     *
     *   - Only pass on the 'foo' and 'bar' variables:
     *       prependInclude("@Contao/_my_include.html.twig", ['foo', 'bar'])
     *
     *   - Only pass on the 'foo' variable but redeclare it as 'foobar':
     *       prependInclude("@Contao/_my_include.html.twig", ['foo' => 'foobar'])
     */
    public function prependInclude(string $template, array $with = null): void
    {
        $this->prependNode($this->getIncludeNode($template, $with));
    }

    /**
     * Include another template ({% include 'template' %}) at the end of the
     * template.
     *
     * You can optionally define how the template's context should be passed
     * on to the included template by providing a $with mapping.
     *
     * Examples:
     *
     *   - Pass the full context on to the included template:
     *      appendInclude("@Contao/_my_include.html.twig")
     *
     *   - Only pass on the 'foo' and 'bar' variables:
     *       appendInclude("@Contao/_my_include.html.twig", ['foo', 'bar'])
     *
     *   - Only pass on the 'foo' variable but redeclare it as 'foobar':
     *       appendInclude("@Contao/_my_include.html.twig", ['foo' => 'foobar'])
     */
    public function appendInclude(string $template, array $with = null): void
    {
        $this->appendNode($this->getIncludeNode($template, $with));
    }

    private function getPrintNode(string $content): PrintNode
    {
        return new PrintNode(
            new ConstantExpression($content, 0),
            0
        );
    }

    private function getIncludeNode(string $template, ?array $with): IncludeNode
    {
        if ($this->getName() === $template) {
            throw new \InvalidArgumentException("You cannot append the template '$template' to itself as this would cause infinite recursion. Did you miss a guard for '\$event->getName()' when implementing the event listener?");
        }

        $variables = null;

        if (null !== $with) {
            $variables = new ArrayExpression([], 0);

            foreach ($with as $currentContextKey => $newContextKey) {
                if (\is_int($currentContextKey)) {
                    $currentContextKey = $newContextKey;
                }

                $variables->addElement(
                    new NameExpression($currentContextKey, 0),
                    new ConstantExpression($newContextKey, 0),
                );
            }
        }

        return new IncludeNode(
            new ConstantExpression($template, 0),
            $variables,
            null !== $variables,
            false,
            0
        );
    }

    private function prependNode(Node $node): void
    {
        $this->node->setNode(
            'display_start',
            new Node([$node, ...$this->node->getNode('display_start')])
        );
    }

    private function appendNode(Node $node): void
    {
        $this->node->setNode(
            'display_end',
            new Node([...$this->node->getNode('display_end'), $node])
        );
    }
}
