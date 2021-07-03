<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Inheritance;

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Inheritance\TokenParserHelper;
use Twig\Node\Expression\Binary\EqualBinary;
use Twig\Node\Expression\ConditionalExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\NameExpression;
use Twig\Node\Node;

class TokenParserHelperTest extends TestCase
{
    /**
     * @dataProvider provideNameInputs
     */
    public function testGetContaoTemplate(string $name, ?string $expected): void
    {
        $this->assertSame($expected, TokenParserHelper::getContaoTemplate($name));
    }

    public function provideNameInputs(): \Generator
    {
        yield 'extract name from Contao template' => [
            '@Contao/foo.html.twig',
            'foo.html.twig',
        ];

        yield 'extract name from Contao template without extension' => [
            '@Contao/foo',
            'foo',
        ];

        yield 'ignore other namespaces' => [
            '@Contao_Thing/foo.html.twig',
            null,
        ];

        yield 'ignore arbitrary input' => [
            'foo',
            null,
        ];
    }

    public function testTraversesNodeTree(): void
    {
        $tree = new ConditionalExpression(
            new EqualBinary(
                new NameExpression('x', 1),
                $n1 = new ConstantExpression(1, 1),
                1,
            ),
            $n2 = new ConstantExpression('a.html.twig', 1),
            $n3 = new ConstantExpression('b.html.twig', 1),
            1
        );

        $nodes = [];

        TokenParserHelper::traverseConstantExpressions(
            $tree,
            static function (Node $node) use (&$nodes): void {
                $nodes[] = $node;
            }
        );

        $this->assertSame([$n1, $n2, $n3], $nodes);
    }
}
