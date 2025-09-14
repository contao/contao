<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Studio;

use Contao\ArrayUtil;
use Twig\Environment;
use Twig\TokenParser\TokenParserInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

class EnvironmentInformation
{
    public const FILENAME = 'environment.json';

    public function __construct(private readonly Environment $twig)
    {
    }

    public function dump(): array
    {
        // We output the keywords sorted and organized by length and occurrence, so that
        // they can be easily matched by a regular expression
        $normalize = static function (array $array): array {
            rsort($array);

            return array_values($array);
        };

        $tokenParsers = $this->twig->getTokenParsers();

        // Guess which tags have a corresponding end tag by method naming convention
        $tokenParsersWithEndTag = array_filter(
            $tokenParsers,
            static fn (TokenParserInterface $tokenParser): bool => method_exists($tokenParser, \sprintf('decide%sEnd', ucfirst($tokenParser->getTag()))),
        );

        $tags = $normalize(
            array_map(
                static fn (TokenParserInterface $tokenParser): string => $tokenParser->getTag(),
                $tokenParsers,
            ),
        );

        // Handle some special cases
        ArrayUtil::arrayInsert($tags, array_search('if', $tags, true), 'elseif');

        $tags = [
            ...$tags,
            ...$normalize(
                array_map(
                    static fn (TokenParserInterface $tokenParser): string => "end{$tokenParser->getTag()}",
                    $tokenParsersWithEndTag,
                ),
            ),
        ];

        return [
            'tags' => $tags,
            'functions' => $normalize(
                array_map(
                    static fn (TwigFunction $function): string => $function->getName(),
                    $this->twig->getFunctions(),
                ),
            ),
            'filters' => $normalize(
                array_map(
                    static fn (TwigFilter $filter): string => $filter->getName(),
                    $this->twig->getFilters(),
                ),
            ),
            'tests' => $normalize(
                array_map(
                    static fn (TwigTest $test): string => $test->getName(),
                    $this->twig->getTests(),
                ),
            ),
        ];
    }
}
