<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Slots;

use Twig\Error\SyntaxError;
use Twig\Node\EmptyNode;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\Filter\RawFilter;
use Twig\Node\Expression\FilterExpression;
use Twig\Node\Expression\FunctionExpression;
use Twig\Node\Expression\GetAttrExpression;
use Twig\Node\Expression\Variable\ContextVariable;
use Twig\Node\Node;
use Twig\Node\PrintNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * @internal
 */
final class SlotTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): Node
    {
        $stream = $this->parser->getStream();

        // Parse opening tag: {% slot %}
        $nameToken = $stream->expect(Token::NAME_TYPE, null, '');
        $stream->expect(Token::BLOCK_END_TYPE);

        $body = $this->parser->subparse($this->decideForFork(...));

        $throwMissingSlotFunctionError = static function () use ($nameToken, $token, $stream): void {
            throw new SyntaxError(\sprintf('Slot "%s" adds template content but does not call the "slot()" function.', $nameToken->getValue()), $token->getLine(), $stream->getSourceContext());
        };

        if ($body->count()) {
            if (0 === $this->traverseAndReplaceSlotFunction($nameToken->getValue(), $body)) {
                $throwMissingSlotFunctionError();
            }
        } elseif ($body->hasAttribute('data') && $body->getAttribute('data')) {
            $throwMissingSlotFunctionError();
        } else {
            $line = $stream->getCurrent()->getLine();
            $body->setNode('body', new PrintNode($this->getSlotReferenceExpression($nameToken->getValue(), $line), $line));
        }

        // Parse optional {% else %} tag with fallback content
        if ('else' === $stream->next()->getValue()) {
            $stream->expect(Token::BLOCK_END_TYPE);
            $fallback = $this->parser->subparse($this->decideSlotEnd(...), true);
        } else {
            $fallback = null;
        }

        $stream->expect(Token::BLOCK_END_TYPE);

        return new SlotNode($nameToken->getValue(), $body, $fallback, $token->getLine());
    }

    public function decideForFork(Token $token): bool
    {
        return $token->test(['else', 'endslot']);
    }

    /**
     * Keep the name of this function consistent - we use it to guess which token
     * parsers have corresponding end tags.
     *
     * @see \Contao\CoreBundle\Twig\EnvironmentInformation
     */
    public function decideSlotEnd(Token $token): bool
    {
        return $token->test('endslot');
    }

    public function getTag(): string
    {
        return 'slot';
    }

    /**
     * Returns the number of slot function expressions that were replaced.
     */
    private function traverseAndReplaceSlotFunction(string $name, Node $node, Node|null $parent = null): int
    {
        if ($node instanceof FunctionExpression && 'slot' === $node->getAttribute('name')) {
            /** @var Node $target */
            $target = $parent;

            foreach (array_keys(iterator_to_array($target)) as $key) {
                $target->removeNode((string) $key);
            }

            $target->setNode('expr', $this->getSlotReferenceExpression($name, $target->getTemplateLine()));

            return 1;
        }

        $count = 0;

        foreach ($node as $child) {
            $count += $this->traverseAndReplaceSlotFunction($name, $child, $node);
        }

        return $count;
    }

    /**
     * Builds an expression that is equivalent to "_slots.<name>|raw".
     */
    private function getSlotReferenceExpression(string $name, int $line): AbstractExpression
    {
        $node = new GetAttrExpression(
            new ContextVariable('_slots', $line),
            new ConstantExpression($name, $line),
            null,
            'array',
            $line,
        );

        if (class_exists(RawFilter::class)) {
            return new RawFilter($node);
        }

        return new FilterExpression(
            $node,
            new ConstantExpression('raw', $line),
            new EmptyNode($line),
            $line,
        );
    }
}
