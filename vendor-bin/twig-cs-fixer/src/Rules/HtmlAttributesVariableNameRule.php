<?php

declare(strict_types=1);

namespace Contao\Tools\TwigCsFixer\Rules;

use TwigCsFixer\Rules\AbstractRule;
use TwigCsFixer\Token\Token;
use TwigCsFixer\Token\Tokens;

/**
 * Ensures that variables that are assigned an HtmlAttributes instance via the
 * "attrs()" function have a suffix "_attributes" in their name.
 *
 * Examples of valid naming:
 *   {% set foo_attributes = attrs() […] %}
 *   {% set foo_bar_attributes = attrs() […] %}
 *   {% set attributes = attrs() […] %}
 *
 * Examples of invalid naming:
 *   {% set bar = attrs() […] %}
 *   {% set bar_attrs = attrs() […] %}
 *   {% set barattributes = attrs() […] %}
 */
final class HtmlAttributesVariableNameRule extends AbstractRule
{
    private const EXPECTED_SUFFIX = 'attributes';

    protected function process(int $tokenIndex, Tokens $tokens): void
    {
        $token = $tokens->get($tokenIndex);

        if (!$token->isMatching(Token::BLOCK_NAME_TYPE, 'set')) {
            return;
        }

        $nameTokenIndex = $tokens->findNext(Token::NAME_TYPE, $tokenIndex);

        $valueToken = $tokens->get(
            $tokens->findNext(
                [...Token::INDENT_TOKENS, Token::OPERATOR_TYPE],
                $nameTokenIndex + 1,
                exclude: true,
            )
        );

        if ($valueToken->isMatching(Token::FUNCTION_NAME_TYPE, 'attrs')) {
            $this->validateAttrsVariable($tokens->get($nameTokenIndex));
        }
    }

    private function validateAttrsVariable(Token $token): void
    {
        $name = $token->getValue();

        if (1 === preg_match(sprintf('/(?:^|\w_)%s$/', preg_quote(self::EXPECTED_SUFFIX, '/')), $name)) {
            return;
        }

        $this->addError(
            \sprintf(
                'The variable name storing the result of an "attrs()" function must have the "%s" suffix, got "%s".',
                self::EXPECTED_SUFFIX,
                $name
            ),
            $token,
        );
    }
}
