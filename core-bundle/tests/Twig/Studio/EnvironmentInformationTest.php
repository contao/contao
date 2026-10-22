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
use Twig\Node\Node;
use Twig\Parser;
use Twig\Token;
use Twig\TokenParser\TokenParserInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

class EnvironmentInformationTest extends TestCase
{
    public function testGetEnvironmentInformation(): void
    {
        $tokenParser = $this->createStub(TokenParserInterface::class);
        $tokenParser
            ->method('getTag')
            ->willReturn('if')
        ;

        $tokenParserWithEndTag = new class() implements TokenParserInterface {
            public function setParser(Parser $parser): void
            {
            }

            public function parse(Token $token): Node
            {
                throw new \RuntimeException('Not implemented');
            }

            public function getTag(): string
            {
                return 'region';
            }

            public function decideRegionEnd(): void
            {
                // Marker function
            }
        };

        $environment = $this->createStub(Environment::class);
        $environment
            ->method('getTokenParsers')
            ->willReturn([$tokenParser, $tokenParserWithEndTag])
        ;

        $environment
            ->method('getFunctions')
            ->willReturn([new TwigFunction('a_function'), new TwigFunction('123'), new TwigFunction('z_function')])
        ;

        $environment
            ->method('getFilters')
            ->willReturn([new TwigFilter('a_filter'), new TwigFilter('z_filter')])
        ;

        $environment
            ->method('getTests')
            ->willReturn([new TwigTest('a_test'), new TwigTest('z_test')])
        ;

        $information = new EnvironmentInformation($environment);

        $this->assertSame(
            [
                'tags' => ['region', 'elseif', 'else', 'if', 'endregion'],
                'functions' => ['z_function', 'a_function', '123'],
                'filters' => ['z_filter', 'a_filter'],
                'tests' => ['z_test', 'a_test'],
            ],
            $information->getData(),
        );

        $this->assertSame(
            ['items' => [['kind' => 'tag', 'name' => 'if'], ['kind' => 'tag', 'name' => 'region']]],
            $information->discover('tag'),
        );
    }
}
