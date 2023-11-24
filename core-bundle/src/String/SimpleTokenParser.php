<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\String;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LogLevel;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\Lexer;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use Symfony\Component\ExpressionLanguage\Token;

class SimpleTokenParser implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(private readonly ExpressionLanguage $expressionLanguage)
    {
    }

    /**
     * Parse simple tokens.
     *
     * @param array $tokens Key value pairs ([token => value, ...])
     *
     * @throws \RuntimeException         If $subject cannot be parsed
     * @throws \InvalidArgumentException If there are incorrectly formatted if-tags
     */
    public function parse(string $subject, array $tokens, bool $allowHtml = true): string
    {
        // The last item is true if it is inside a matching if-tag
        $stack = [true];

        // The last item is true if any if/elseif at that level was true
        $ifStack = [true];

        // Tokenize the string into tag and text blocks
        $tags = preg_split(
            $allowHtml
                ? '/((?:{|&#123;)(?:(?!&#12[35];)[^{}])+(?:}|&#125;))\n?/'
                : '/({[^{}]+})\n?/',
            $subject,
            -1,
            PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY,
        );

        // Parse the tokens
        $return = '';

        foreach ($tags as $tag) {
            $decodedTag = $allowHtml
                ? html_entity_decode($tag, ENT_QUOTES, 'UTF-8')
                : $tag;

            // True if it is inside a matching if-tag
            $current = $stack[\count($stack) - 1];
            $currentIf = $ifStack[\count($ifStack) - 1];

            if (str_starts_with($decodedTag, '{if ')) {
                $expression = $this->evaluateExpression(substr($decodedTag, 4, -1), $tokens);
                $stack[] = $current && $expression;
                $ifStack[] = $expression;
            } elseif (str_starts_with($decodedTag, '{elseif ')) {
                $expression = $this->evaluateExpression(substr($decodedTag, 8, -1), $tokens);
                array_pop($stack);
                array_pop($ifStack);
                $stack[] = !$currentIf && $stack[\count($stack) - 1] && $expression;
                $ifStack[] = $currentIf || $expression;
            } elseif (str_starts_with($decodedTag, '{else}')) {
                array_pop($stack);
                array_pop($ifStack);
                $stack[] = !$currentIf && $stack[\count($stack) - 1];
                $ifStack[] = true;
            } elseif (str_starts_with($decodedTag, '{endif}')) {
                array_pop($stack);
                array_pop($ifStack);
            } elseif ($current) {
                $return .= $this->replaceTokens($tag, $tokens);
            }
        }

        if (1 !== \count($stack)) {
            throw new \RuntimeException('Error parsing simple tokens');
        }

        return $return;
    }

    private function replaceTokens(string $subject, array $data): string
    {
        // Replace tokens
        return preg_replace_callback(
            '/##([^=!<>\s]+?)##/',
            function (array $matches) use ($data) {
                if (!\array_key_exists($matches[1], $data)) {
                    $this->logger?->log(LogLevel::INFO, sprintf('Tried to parse unknown simple token "%s".', $matches[1]));

                    return '##'.$matches[1].'##';
                }

                return $data[$matches[1]];
            },
            $subject,
        );
    }

    private function evaluateExpression(string $expression, array $data): bool
    {
        $unmatchedVariables = array_diff($this->getVariables($expression), array_keys($data));

        if ($unmatchedVariables) {
            $this->logUnmatchedVariables(...$unmatchedVariables);

            // Define variables that weren't provided with the value 'null'
            $data = [
                ...array_combine($unmatchedVariables, array_fill(0, \count($unmatchedVariables), null)),
                ...$data,
            ];
        }

        try {
            return (bool) $this->expressionLanguage->evaluate($expression, $data);
        } catch (\Throwable $e) {
            throw new \InvalidArgumentException($e->getMessage(), 0, $e);
        }
    }

    private function getVariables(string $expression): array
    {
        $tokens = [];

        try {
            $tokenStream = (new Lexer())->tokenize($expression);

            while (!$tokenStream->isEOF()) {
                $tokens[] = $tokenStream->current;
                $tokenStream->next();
            }
        } catch (SyntaxError) {
            // We cannot identify the variables if tokenizing fails
            return [];
        }

        $variables = [];

        for ($i = 0, $c = \count($tokens); $i < $c; ++$i) {
            if (!$tokens[$i]->test(Token::NAME_TYPE)) {
                continue;
            }

            $value = $tokens[$i]->value;

            // Skip constant nodes (see Symfony/Component/ExpressionLanguage/Parser#parsePrimaryExpression()
            if (\in_array($value, ['true', 'TRUE', 'false', 'FALSE', 'null'], true)) {
                continue;
            }

            // Skip functions
            if (isset($tokens[$i + 1]) && '(' === $tokens[$i + 1]->value) {
                ++$i;

                continue;
            }

            if (!\in_array($value, $variables, true)) {
                $variables[] = $value;
            }
        }

        return $variables;
    }

    private function logUnmatchedVariables(string ...$tokenNames): void
    {
        $this->logger?->log(
            LogLevel::INFO,
            sprintf('Tried to evaluate unknown simple token(s): "%s".', implode('", "', $tokenNames)),
        );
    }
}
