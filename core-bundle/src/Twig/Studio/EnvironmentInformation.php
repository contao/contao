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
        return [
            'tags' => array_values(
                array_map(
                    static fn (TokenParserInterface $tokenParser): string => $tokenParser->getTag(),
                    $this->twig->getTokenParsers(),
                ),
            ),
            'filters' => array_values(
                array_map(
                    static fn (TwigFilter $filter): string => $filter->getName(),
                    $this->twig->getFilters(),
                ),
            ),
            'functions' => array_values(
                array_map(
                    static fn (TwigFunction $function): string => $function->getName(),
                    $this->twig->getFunctions(),
                ),
            ),
            'tests' => array_values(
                array_map(
                    static fn (TwigTest $test): string => $test->getName(),
                    $this->twig->getTests(),
                ),
            ),
        ];
    }
}
