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

use Contao\MakerBundle\Reflection\MethodDefinition;
use Contao\MakerBundle\Reflection\SignatureGenerator;
use Contao\Module;
use Contao\Widget;
use PHPUnit\Framework\TestCase;

class SignatureGeneratorTest extends TestCase
{
    /**
     * @param array<array> $parameters
     *
     * @dataProvider methodProvider
     */
    public function testSignatureCreation(string $signature, string|null $returnType, array $parameters): void
    {
        $generator = new SignatureGenerator();
        $method = new MethodDefinition($returnType, $parameters);

        $this->assertSame($signature, $generator->generate($method, '__invoke'));
    }

    public function methodProvider(): \Generator
    {
        yield [
            'public function __invoke(array $events, array $calendars, int $timeStart, int $timeEnd, Module $module): array',
            'array',
            [
                'events' => 'array',
                'calendars' => 'array',
                'timeStart' => 'int',
                'timeEnd' => 'int',
                'module' => Module::class,
            ],
        ];

        yield [
            'public function __invoke(array $fragments): array',
            'array',
            [
                'fragments' => 'array',
            ],
        ];

        yield [
            'public function __invoke(string $key, string $value, string $definition, array &$dataSet): ?array',
            '?array',
            [
                'key' => 'string',
                'value' => 'string',
                'definition' => 'string',
                '&dataSet' => 'array',
            ],
        ];

        yield 'empty parameters' => [
            'public function __invoke(): void',
            'void',
            [],
        ];

        yield 'no return type given' => [
            'public function __invoke()',
            null,
            [],
        ];

        yield 'untyped parameters' => [
            'public function __invoke($key, $value)',
            null,
            [
                'key' => null,
                'value' => null,
            ],
        ];

        yield 'default values' => [
            'public function __invoke(array $pages, int $rootId = null, bool $isSitemap = false, string $language = null): array',
            'array',
            [
                'pages' => 'array',
                'rootId' => ['int', 'null'],
                'isSitemap' => ['bool', 'false'],
                'language' => ['string', 'null'],
            ],
        ];

        yield 'class parameters/class return types' => [
            'public function __invoke(Widget $widget): Widget',
            Widget::class,
            [
                'widget' => Widget::class,
            ],
        ];
    }
}
