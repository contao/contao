<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\DependencyInjection\Compiler;

use Contao\CoreBundle\DependencyInjection\Attribute\AsBlockInsertTag;
use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag;
use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTagFlag;
use Contao\CoreBundle\DependencyInjection\Compiler\AddInsertTagsPass;
use Contao\CoreBundle\InsertTag\Flag\PhpFunctionFlag;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\InsertTag\Resolver\DateInsertTag;
use Contao\CoreBundle\InsertTag\Resolver\IfLanguageInsertTag;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidDefinitionException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class AddInsertTagsPassTest extends TestCase
{
    public function testDoesNothingIfNoParserDefined(): void
    {
        $container = new ContainerBuilder();

        $pass = new AddInsertTagsPass();
        $pass->process($container);

        $definitions = $container->getDefinitions();
        unset($definitions['service_container']);

        $this->assertEmpty($definitions);
    }

    public function testDoesNothingIfNoTaggedServicesDefined(): void
    {
        $container = $this->getContainerBuilder();

        $pass = new AddInsertTagsPass();
        $pass->process($container);

        $definition = $container->getDefinition('contao.insert_tag.parser');

        $this->assertEmpty($definition->getMethodCalls());
    }

    /**
     * @dataProvider getAddsExpectedMethodCalls
     */
    public function testAddsExpectedMethodCalls(array $services, array $expectedMethodCalls, \Throwable|null $expectedException = null): void
    {
        $container = $this->getContainerBuilder();

        foreach ($services as $serviceId => $definition) {
            $container->setDefinition($serviceId, $definition);
        }

        $pass = new AddInsertTagsPass();

        if ($expectedException instanceof \Throwable) {
            $this->expectException($expectedException::class);
            $this->expectExceptionMessage($expectedException->getMessage());
        }

        $pass->process($container);

        $definition = $container->getDefinition('contao.insert_tag.parser');

        foreach ($definition->getMethodCalls() as $index => $methodCall) {
            $expected = $expectedMethodCalls[$index];
            $this->assertSame($expected[0], $methodCall[0]);

            if ('addFlagCallback' === $methodCall[0]) {
                $this->assertSame($expected[1], $methodCall[1][0]);
                $this->assertSame($expected[2], (string) $methodCall[1][1]);
                $this->assertSame($expected[3], $methodCall[1][2]);
            } else {
                $this->assertSame($expected[1], (string) $methodCall[1][0]->getArgument(0));
                $this->assertSame($expected[2], $methodCall[1][0]->getArgument(1));
                $this->assertSame($expected[3], $methodCall[1][0]->getArgument(2));
                $this->assertSame($expected[4], $methodCall[1][0]->getArgument(3));
                $this->assertSame($expected[5], $methodCall[1][0]->getArgument(4));
                $this->assertSame($expected[6], $methodCall[1][0]->getArgument(5));
            }
        }
    }

    public function getAddsExpectedMethodCalls(): \Generator
    {
        yield [
            [
                'service_a' => (new Definition(DateInsertTag::class))->addTag('contao.insert_tag', get_object_vars(new AsInsertTag('date'))),
            ],
            [
                ['addSubscription', 'service_a', '__invoke', 'date', null, true, false],
            ],
        ];

        yield [
            [
                'service_a' => (new Definition(IfLanguageInsertTag::class))->addTag('contao.block_insert_tag', get_object_vars(new AsBlockInsertTag('iflng', 'iflng'))),
            ],
            [
                ['addBlockSubscription', 'service_a', '__invoke', 'iflng', 'iflng', true, false],
            ],
        ];

        yield [
            [
                'service_a' => (new Definition(PhpFunctionFlag::class))->addTag('contao.insert_tag_flag', get_object_vars(new AsInsertTagFlag('some_flag'))),
            ],
            [
                ['addFlagCallback', 'some_flag', 'service_a', '__invoke'],
            ],
        ];

        yield [
            [
                'service_a' => (new Definition(DateInsertTag::class))->addTag('contao.insert_tag', get_object_vars(new AsInsertTag('date'))),
                'service_b' => (new Definition(IfLanguageInsertTag::class))->addTag('contao.block_insert_tag', get_object_vars(new AsBlockInsertTag('iflng', 'iflng'))),
                'service_c' => (new Definition(PhpFunctionFlag::class))->addTag('contao.insert_tag_flag', get_object_vars(new AsInsertTagFlag('some_flag'))),
                'service_d' => (new Definition(DateInsertTag::class))->addTag('contao.insert_tag', get_object_vars(new AsInsertTag('not_date', true, 1, '__invoke', false))),
                'service_e' => (new Definition(IfLanguageInsertTag::class))->addTag('contao.block_insert_tag', get_object_vars(new AsBlockInsertTag('ifnlng', 'end_ifnlng', priority: -1))),
                'service_f' => (new Definition(PhpFunctionFlag::class))->addTag('contao.insert_tag_flag', get_object_vars(new AsInsertTagFlag('some_flag', 0, '__invoke'))),
            ],
            [
                ['addSubscription', 'service_a', '__invoke', 'date', null, true, false],
                ['addSubscription', 'service_d', '__invoke', 'not_date', null, false, true],
                ['addBlockSubscription', 'service_e', '__invoke', 'ifnlng', 'end_ifnlng', true, false],
                ['addBlockSubscription', 'service_b', '__invoke', 'iflng', 'iflng', true, false],
                ['addFlagCallback', 'some_flag', 'service_c', '__invoke'],
                ['addFlagCallback', 'some_flag', 'service_f', '__invoke'],
            ],
        ];

        yield [
            [
                'service_a' => (new Definition(DateInsertTag::class))->addTag('contao.insert_tag', get_object_vars(new AsInsertTag('invalid-tag'))),
            ],
            [],
            new InvalidDefinitionException('Invalid insert tag name "invalid-tag"'),
        ];

        yield [
            [
                'service_a' => (new Definition(IfLanguageInsertTag::class))->addTag('contao.block_insert_tag', get_object_vars(new AsBlockInsertTag('iflng', 'invalid-tag'))),
            ],
            [],
            new InvalidDefinitionException('Invalid insert tag end tag name "invalid-tag"'),
        ];

        yield [
            [
                'service_a' => (new Definition(PhpFunctionFlag::class))->addTag('contao.insert_tag_flag', get_object_vars(new AsInsertTagFlag('invalid-flag'))),
            ],
            [],
            new InvalidDefinitionException('Invalid insert tag flag name "invalid-flag"'),
        ];

        yield [
            [
                'service_a' => (new Definition(DateInsertTag::class))->addTag('contao.insert_tag', get_object_vars(new AsInsertTag('date', method: 'notExists'))),
            ],
            [],
            new InvalidDefinitionException('The contao.insert_tag definition for service "service_a" is invalid. The class "Contao\CoreBundle\InsertTag\Resolver\DateInsertTag" does not have a method "notExists".'),
        ];

        yield [
            [
                'service_a' => (new Definition(IfLanguageInsertTag::class))->addTag('contao.insert_tag', get_object_vars(new AsInsertTag('date', method: 'languageMatchesPage'))),
            ],
            [],
            new InvalidDefinitionException('The contao.insert_tag definition for service "service_a" is invalid. The "Contao\CoreBundle\InsertTag\Resolver\IfLanguageInsertTag::languageMatchesPage" method exists but is not public.'),
        ];

        yield [
            [
                'service_a' => (new Definition(IfLanguageInsertTag::class))->addTag('contao.insert_tag', get_object_vars(new AsInsertTag('date'))),
            ],
            [],
            new InvalidDefinitionException('The "Contao\CoreBundle\InsertTag\Resolver\IfLanguageInsertTag::__invoke" method exists but has an invalid return type.'),
        ];

        yield [
            [
                'service_a' => (new Definition(DateInsertTag::class))->addTag('contao.block_insert_tag', get_object_vars(new AsBlockInsertTag('foo', 'bar'))),
            ],
            [],
            new InvalidDefinitionException('The "Contao\CoreBundle\InsertTag\Resolver\DateInsertTag::__invoke" method exists but has an invalid return type.'),
        ];
    }

    private function getContainerBuilder(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setDefinition(
            'contao.insert_tag.parser',
            new Definition(
                InsertTagParser::class,
                [
                    new Reference('contao.framework'),
                    new Reference('monolog.logger.contao.error'),
                    new Reference('fragment.handler'),
                    new Reference('request_stack'),
                    null,
                    ['*'],
                ]
            )
        );

        return $container;
    }
}
