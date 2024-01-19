<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\MakerBundle\Tests\Reflection;

use Contao\MakerBundle\Fixtures\ClassOne;
use Contao\MakerBundle\Fixtures\ClassThree;
use Contao\MakerBundle\Fixtures\ClassTwo;
use Contao\MakerBundle\Reflection\ImportExtractor;
use Contao\MakerBundle\Reflection\MethodDefinition;
use PHPUnit\Framework\TestCase;

class ImportExtractorTest extends TestCase
{
    /**
     * @dataProvider methodProvider
     */
    public function testExtraction(array $uses, MethodDefinition $method): void
    {
        $this->assertSame($uses, (new ImportExtractor())->extract($method));
    }

    public function methodProvider(): \Generator
    {
        yield 'empty return type and parameter list' => [
            [],
            new MethodDefinition('void', []),
        ];

        yield 'single class in return type' => [
            [
                ClassOne::class,
            ],
            new MethodDefinition(ClassOne::class, []),
        ];

        yield 'single return type, single parameter' => [
            [
                ClassOne::class,
                ClassTwo::class,
            ],
            new MethodDefinition(ClassOne::class, [
                'arg1' => ClassTwo::class,
            ]),
        ];

        yield 'multiple parameters' => [
            [
                ClassOne::class,
                ClassThree::class,
                ClassTwo::class,
            ],
            new MethodDefinition(ClassOne::class, [
                'arg1' => ClassTwo::class,
                'arg2' => ClassThree::class,
            ]),
        ];

        yield 'multiple parameters with the same type' => [
            [
                ClassOne::class,
            ],
            new MethodDefinition('void', [
                'arg1' => ClassOne::class,
                'arg2' => ClassOne::class,
            ]),
        ];

        yield 'same return type and parameter' => [
            [
                ClassOne::class,
            ],
            new MethodDefinition(ClassOne::class, [
                'arg1' => ClassOne::class,
            ]),
        ];
    }
}
