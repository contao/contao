<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Studio;

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Studio\EnvironmentInformation;
use Twig\Environment;
use Twig\TokenParser\TokenParserInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

class EnvironmentInformationTest extends TestCase
{
    public function testGetEnvironmentInformation(): void
    {
        $tokenParser = $this->createMock(TokenParserInterface::class);
        $tokenParser
            ->method('getTag')
            ->willReturn('tag')
        ;

        $environment = $this->createMock(Environment::class);
        $environment
            ->method('getTokenParsers')
            ->willReturn([$tokenParser])
        ;

        $environment
            ->method('getFunctions')
            ->willReturn([new TwigFunction('function')])
        ;

        $environment
            ->method('getFilters')
            ->willReturn([new TwigFilter('filter')])
        ;

        $environment
            ->method('getTests')
            ->willReturn([new TwigTest('test')])
        ;

        $this->assertSame(
            [
                'tags' => ['tag'],
                'filters' => ['filter'],
                'functions' => ['function'],
                'tests' => ['test'],
            ],
            (new EnvironmentInformation($environment))->dump(),
        );
    }
}
