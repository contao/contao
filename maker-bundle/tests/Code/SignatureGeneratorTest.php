<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\MakerBundle\Tests\Code;

use Contao\MakerBundle\Code\SignatureGenerator;
use Contao\MakerBundle\Model\MethodDefinition;
use PHPUnit\Framework\TestCase;

class SignatureGeneratorTest extends TestCase
{
    /**
     * @dataProvider methodProvider
     *
     * @param array<array> $parameters
     */
    public function testSignatureCreation(string $signature, ?string $returnType, array $parameters): void
    {
        $generator = new SignatureGenerator();
        $method = new MethodDefinition($returnType, $parameters);

        $this->assertSame($signature, $generator->generate($method, '__invoke'));
    }

    /**
     * @return array<array>
     */
    public function methodProvider(): array
    {
        return [
            [
                'public function __invoke(array $events, array $calendars, int $timeStart, int $timeEnd, Module $module): array',
                'array',
                [
                    'events' => 'array',
                    'calendars' => 'array',
                    'timeStart' => 'int',
                    'timeEnd' => 'int',
                    'module' => 'Contao\Module',
                ],
            ],
            [
                'public function __invoke(array $fragments): array',
                'array',
                [
                    'fragments' => 'array',
                ],
            ],
            [
                'public function __invoke(string $key, string $value, string $definition, array &$dataSet): ?array',
                '?array',
                [
                    'key' => 'string',
                    'value' => 'string',
                    'definition' => 'string',
                    '&dataSet' => 'array',
                ],
            ],

            // Empty parameters
            [
                'public function __invoke(): void',
                'void',
                [],
            ],

            // No return type given
            [
                'public function __invoke()',
                null,
                [],
            ],

            // Untyped parameters
            [
                'public function __invoke($key, $value)',
                null,
                [
                    'key' => null,
                    'value' => null,
                ],
            ],

            // Default values
            [
                'public function __invoke(array $pages, int $rootId = null, bool $isSitemap = false, string $language = null): array',
                'array',
                [
                    'pages' => 'array',
                    'rootId' => ['int', 'null'],
                    'isSitemap' => ['bool', 'false'],
                    'language' => ['string', 'null'],
                ],
            ],

            // Class parameters/class return types
            [
                'public function __invoke(Widget $widget): Widget',
                'Contao\Widget',
                [
                    'widget' => 'Contao\Widget',
                ],
            ],
        ];
    }
}
