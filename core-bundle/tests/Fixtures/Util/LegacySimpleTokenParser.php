<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Fixtures\Util;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LogLevel;

class LegacySimpleTokenParser implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * This is a copy of the StringUtil::parseSimpleTokens implementation prior
     * to the introduction of parsing with the Symfony expression language.
     *
     * The only change to the original code is the use of DI for logging for
     * easier unit tests.
     */
    public function parse($strString, $arrData)
    {
        $strReturn = '';

        $replaceTokens = function ($strSubject) use ($arrData) {
            // Replace tokens
            return preg_replace_callback(
                '/##([^=!<>\s]+?)##/',
                function (array $matches) use ($arrData) {
                    if (!\array_key_exists($matches[1], $arrData)) {
                        $this->logger->log(LogLevel::INFO, sprintf('Tried to parse unknown simple token "%s".', $matches[1]));

                        return '##'.$matches[1].'##';
                    }

                    return $arrData[$matches[1]];
                },
                $strSubject
            );
        };

        $evaluateExpression = function ($strExpression) use ($arrData) {
            if (!preg_match('/^([^=!<>\s]+) *([=!<>]+)(.+)$/s', $strExpression, $arrMatches)) {
                return false;
            }

            $strToken = $arrMatches[1];
            $strOperator = $arrMatches[2];
            $strValue = trim($arrMatches[3], ' ');

            if (!\array_key_exists($strToken, $arrData)) {
                $this->logger->log(LogLevel::INFO, sprintf('Tried to evaluate unknown simple token "%s".', $strToken));

                return false;
            }

            $varTokenValue = $arrData[$strToken];

            if (is_numeric($strValue)) {
                if (false === strpos($strValue, '.')) {
                    $varValue = (int) $strValue;
                } else {
                    $varValue = (float) $strValue;
                }
            } elseif ('true' === strtolower($strValue)) {
                $varValue = true;
            } elseif ('false' === strtolower($strValue)) {
                $varValue = false;
            } elseif ('null' === strtolower($strValue)) {
                $varValue = null;
            } elseif (0 === strncmp($strValue, '"', 1) && '"' === substr($strValue, -1)) {
                $varValue = str_replace('\"', '"', substr($strValue, 1, -1));
            } elseif (0 === strncmp($strValue, "'", 1) && "'" === substr($strValue, -1)) {
                $varValue = str_replace("\\'", "'", substr($strValue, 1, -1));
            } else {
                throw new \InvalidArgumentException(sprintf('Unknown data type of comparison value "%s".', $strValue));
            }

            switch ($strOperator) {
                case '==':
                    return $varTokenValue === $varValue;

                case '!=':
                    return $varTokenValue !== $varValue;

                case '===':
                    return $varTokenValue === $varValue;

                case '!==':
                    return $varTokenValue !== $varValue;

                case '<':
                    return $varTokenValue < $varValue;

                case '>':
                    return $varTokenValue > $varValue;

                case '<=':
                    return $varTokenValue <= $varValue;

                case '>=':
                    return $varTokenValue >= $varValue;

                default:
                    throw new \InvalidArgumentException(sprintf('Unknown simple token comparison operator "%s".', $strOperator));
            }
        };

        // The last item is true if it is inside a matching if-tag
        $arrStack = [true];

        // The last item is true if any if/elseif at that level was true
        $arrIfStack = [true];

        // Tokenize the string into tag and text blocks
        $arrTags = preg_split('/({[^{}]+})\n?/', $strString, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        // Parse the tokens
        foreach ($arrTags as $strTag) {
            // True if it is inside a matching if-tag
            $blnCurrent = $arrStack[\count($arrStack) - 1];
            $blnCurrentIf = $arrIfStack[\count($arrIfStack) - 1];

            if (0 === strncmp($strTag, '{if ', 4)) {
                $blnExpression = $evaluateExpression(substr($strTag, 4, -1));
                $arrStack[] = $blnCurrent && $blnExpression;
                $arrIfStack[] = $blnExpression;
            } elseif (0 === strncmp($strTag, '{elseif ', 8)) {
                $blnExpression = $evaluateExpression(substr($strTag, 8, -1));
                array_pop($arrStack);
                array_pop($arrIfStack);
                $arrStack[] = !$blnCurrentIf && $arrStack[\count($arrStack) - 1] && $blnExpression;
                $arrIfStack[] = $blnCurrentIf || $blnExpression;
            } elseif (0 === strncmp($strTag, '{else}', 6)) {
                array_pop($arrStack);
                array_pop($arrIfStack);
                $arrStack[] = !$blnCurrentIf && $arrStack[\count($arrStack) - 1];
                $arrIfStack[] = true;
            } elseif (0 === strncmp($strTag, '{endif}', 7)) {
                array_pop($arrStack);
                array_pop($arrIfStack);
            } elseif ($blnCurrent) {
                $strReturn .= $replaceTokens($strTag);
            }
        }

        // Throw an exception if there is an error
        if (1 !== \count($arrStack)) {
            throw new \Exception('Error parsing simple tokens');
        }

        return $strReturn;
    }
}
