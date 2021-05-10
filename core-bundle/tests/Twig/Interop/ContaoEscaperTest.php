<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Interop;

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Interop\ContaoEscaper;
use Twig\Environment;

class ContaoEscaperTest extends TestCase
{
    /**
     * @dataProvider provideInput
     */
    public function testEscapesStrings($input, string $charset, string $expectedOutput): void
    {
        $output = (new ContaoEscaper())(
            $this->createMock(Environment::class),
            $input,
            $charset
        );

        $this->assertSame($expectedOutput, $output);
    }

    public function provideInput(): \Generator
    {
        yield 'simple string' => [
            'foo',
            'UTF-8',
            'foo',
        ];

        yield 'integer' => [
            123, 'UTF-8', '123',
        ];

        yield 'string with entities' => [
            'A & B &rarr; &#9829;',
            'UTF-8',
            'A &amp; B &rarr; &#9829;',
        ];

        // fixme: I'd be glad if someone with a good understanding of encoding
        //        could provide some useful examples here. :-)
    }
}
