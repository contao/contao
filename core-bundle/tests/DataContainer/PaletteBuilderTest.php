<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\DataContainer;

use Contao\CoreBundle\DataContainer\PaletteBuilder;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DC_Table;
use Contao\Input;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\MySQLSchemaManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class PaletteBuilderTest extends TestCase
{
    #[DataProvider('getCombinerValues')]
    public function testCombiner(array $source, array $expected): void
    {
        $class = new \ReflectionClass(PaletteBuilder::class);
        $method = $class->getMethod('combiner');
        $names = $method->invoke($class->newInstanceWithoutConstructor(), $source);

        $this->assertSame($expected, array_values($names));
    }

    public static function getCombinerValues(): iterable
    {
        return [
            [
                ['foo'],
                ['foo'],
            ],
            [
                ['foo', 'bar'],
                ['bar', 'foobar', 'foo'],
            ],
            [
                ['foo', 'bar', 'baz'],
                ['baz', 'barbaz', 'bar', 'foobar', 'foobarbaz', 'foobaz', 'foo'],
            ],
            [
                ['foo', 0, 'bar'],
                ['bar', '0bar', 'foo0', 'foo0bar', 'foobar', 'foo'],
            ],
        ];
    }

    #[DataProvider('getGetPaletteValues')]
    public function testGetPalette(string $expected, array $dca, array|null $currentRecord, array|null $postData = null, bool $editAll = false): void
    {
        $GLOBALS['TL_DCA']['tl_foo'] = $dca;

        $dataContainer = $this->createMock(DC_Table::class);
        $dataContainer
            ->method('getCurrentRecord')
            ->with(42, 'tl_foo')
            ->willReturn($currentRecord)
        ;

        $inputAdapter = $this->createAdapterStub(['get', 'post']);
        $inputAdapter
            ->method('get')
            ->with('act')
            ->willReturn($editAll ? 'editAll' : 'edit')
        ;

        $inputAdapter
            ->method('post')
            ->willReturnCallback(static fn ($key) => $postData[$key] ?? null)
        ;

        $framework = $this->createContaoFrameworkStub([Input::class => $inputAdapter]);
        $paletteBuilder = new PaletteBuilder($framework, $this->createMock(RequestStack::class), $this->createMock(Security::class), $this->createMock(Connection::class));

        $this->assertSame($expected, $paletteBuilder->getPalette('tl_foo', 42, $dataContainer));

        unset($GLOBALS['TL_DCA']);
    }

    public static function getGetPaletteValues(): iterable
    {
        yield 'Use default palette without a selector' => [
            'foo',
            [
                'palettes' => [
                    'default' => 'foo',
                ],
            ],
            null,
        ];

        yield 'Use default if current record is null' => [
            'foo',
            [
                'palettes' => [
                    '__selector__' => ['type'],
                    'default' => 'foo',
                ],
            ],
            null,
        ];

        yield 'Use type of current record' => [
            'bar',
            [
                'palettes' => [
                    '__selector__' => ['type'],
                    'default' => 'foo',
                    'foo' => 'bar',
                ],
            ],
            ['type' => 'foo'],
        ];

        yield 'Use type from post data' => [
            'bar',
            [
                'palettes' => [
                    '__selector__' => ['type'],
                    'default' => 'foo',
                    'foo' => 'bar',
                ],
            ],
            ['type' => ''],
            ['FORM_SUBMIT' => 'tl_foo', 'type' => 'foo'],
        ];

        yield 'Use type from post data in editAll mode' => [
            'bar',
            [
                'palettes' => [
                    '__selector__' => ['type'],
                    'default' => 'foo',
                    'foo' => 'bar',
                ],
            ],
            ['type' => ''],
            ['FORM_SUBMIT' => 'tl_foo', 'type_42' => 'foo'],
            true,
        ];

        yield 'Includes subpalette by checkbox name' => [
            'bar,[bar],baz,[EOF]',
            [
                'palettes' => [
                    '__selector__' => ['type', 'bar'],
                    'default' => 'foo',
                    'foo' => 'bar',
                ],
                'subpalettes' => [
                    'bar' => 'baz',
                ],
                'fields' => [
                    'bar' => [
                        'inputType' => 'checkbox',
                    ],
                ],
            ],
            ['type' => 'foo', 'bar' => '1'],
        ];

        yield 'Does not include subpalette from multiple checkbox' => [
            'bar',
            [
                'palettes' => [
                    '__selector__' => ['type', 'bar'],
                    'default' => 'foo',
                    'foo' => 'bar',
                ],
                'subpalettes' => [
                    'bar' => 'baz',
                ],
                'fields' => [
                    'bar' => [
                        'inputType' => 'checkbox',
                        'eval' => ['multiple' => true],
                    ],
                ],
            ],
            ['type' => 'foo', 'bar' => '1'],
        ];

        yield 'Includes subpalette from name-value match' => [
            'bar,[bar],baz,[EOF]',
            [
                'palettes' => [
                    '__selector__' => ['type', 'bar'],
                    'default' => 'foo',
                    'foo' => 'bar',
                ],
                'subpalettes' => [
                    'bar_baz' => 'baz',
                ],
            ],
            ['type' => 'foo', 'bar' => 'baz'],
        ];
    }

    public function testCatchesAccessDeniedOnCurrentRecord(): void
    {
        $GLOBALS['TL_DCA']['tl_foo'] = [
            'palettes' => [
                '__selector__' => ['type'],
                'default' => 'foo',
            ],
        ];

        $dataContainer = $this->createMock(DC_Table::class);
        $dataContainer
            ->expects($this->once())
            ->method('getCurrentRecord')
            ->with(0, 'tl_foo')
            ->willThrowException(new AccessDeniedException())
        ;

        $paletteBuilder = new PaletteBuilder(
            $this->createContaoFrameworkStub(),
            $this->createMock(RequestStack::class),
            $this->createMock(Security::class),
            $this->createMock(Connection::class),
        );

        $this->assertSame('foo', $paletteBuilder->getPalette('tl_foo', 0, $dataContainer));

        unset($GLOBALS['TL_DCA']);
    }

    #[DataProvider('getBoxesValues')]
    public function testGetBoxes(array $expected, array $dca, string $palette, array $fieldsetStates = [], array $tableColumns = [], bool $isGranted = false): void
    {
        $GLOBALS['TL_DCA']['tl_foo'] = $dca;

        $security = $this->createMock(Security::class);
        $security
            ->method('isGranted')
            ->willReturn($isGranted)
        ;

        $paletteBuilder = new PaletteBuilder(
            $this->createContaoFrameworkStub(),
            $this->mockRequestStackWithFieldsetState(['tl_foo' => $fieldsetStates]),
            $security,
            $this->mockConnectionWithTableColumns($tableColumns),
        );

        $this->assertSame($expected, $paletteBuilder->getBoxes($palette, 'tl_foo', [] !== $tableColumns));

        unset($GLOBALS['TL_DCA']);
    }

    public static function getBoxesValues(): iterable
    {
        yield 'No legends' => [
            [
                [
                    'key' => '',
                    'class' => '',
                    'fields' => ['foo', 'bar'],
                ],
            ],
            [
                'fields' => [
                    'foo' => [],
                    'bar' => [],
                ],
            ],
            'foo,bar',
        ];

        yield 'Multiple legends' => [
            [
                [
                    'key' => 'foo_legend',
                    'class' => '',
                    'fields' => ['foo', 'bar'],
                ],
                [
                    'key' => 'baz_legend',
                    'class' => '',
                    'fields' => ['baz'],
                ],
            ],
            [
                'fields' => [
                    'foo' => [],
                    'bar' => [],
                    'baz' => [],
                ],
            ],
            '{foo_legend},foo,bar;{baz_legend},baz',
        ];

        yield 'With subpalettes' => [
            [
                [
                    'key' => 'foo_legend',
                    'class' => '',
                    'fields' => ['foo', '[bar]', 'bar', '[EOF]'],
                ],
            ],
            [
                'fields' => [
                    'foo' => [],
                    'bar' => [],
                ],
            ],
            '{foo_legend},foo,[bar],bar,[EOF]',
        ];

        yield 'Inherits the class from legends' => [
            [
                [
                    'key' => 'foo_legend',
                    'class' => 'custom',
                    'fields' => ['foo'],
                ],
            ],
            [
                'fields' => [
                    'foo' => [],
                ],
            ],
            '{foo_legend:custom},foo',
        ];

        yield 'Converts "hide" to "collapsed" class' => [
            [
                [
                    'key' => 'foo_legend',
                    'class' => 'collapsed',
                    'fields' => ['foo'],
                ],
            ],
            [
                'fields' => [
                    'foo' => [],
                ],
            ],
            '{foo_legend:hide},foo',
        ];

        yield 'Adds the "collapsed" class if fieldset state is collapsed' => [
            [
                [
                    'key' => 'foo_legend',
                    'class' => 'collapsed',
                    'fields' => ['foo'],
                ],
            ],
            [
                'fields' => [
                    'foo' => [],
                ],
            ],
            '{foo_legend},foo',
            [
                'foo_legend' => false,
            ],
        ];

        yield 'Removes the "collapsed" class if fieldset state is open' => [
            [
                [
                    'key' => 'foo_legend',
                    'class' => '',
                    'fields' => ['foo'],
                ],
            ],
            [
                'fields' => [
                    'foo' => [],
                ],
            ],
            '{foo_legend:collapsed},foo',
            [
                'foo_legend' => true,
            ],
        ];

        yield 'Skips fields that do not exist' => [
            [
                [
                    'key' => 'foo_legend',
                    'class' => '',
                    'fields' => ['foo'],
                ],
            ],
            [
                'fields' => [
                    'foo' => [],
                ],
            ],
            '{foo_legend},foo,bar',
        ];

        yield 'Skips field that is excluded if the user does not have alexf' => [
            [
                [
                    'key' => 'foo_legend',
                    'class' => '',
                    'fields' => ['foo'],
                ],
            ],
            [
                'fields' => [
                    'foo' => [],
                    'bar' => [
                        'exclude' => true,
                    ],
                ],
            ],
            '{foo_legend},foo,bar',
        ];

        yield 'Does not skip field that is excluded if the user has alexf' => [
            [
                [
                    'key' => 'foo_legend',
                    'class' => '',
                    'fields' => ['foo', 'bar'],
                ],
            ],
            [
                'fields' => [
                    'foo' => [],
                    'bar' => [
                        'exclude' => true,
                    ],
                ],
            ],
            '{foo_legend},foo,bar',
            [],
            [],
            true,
        ];

        yield 'Skips box if it has no fields' => [
            [
                [
                    'key' => 'foo_legend',
                    'class' => '',
                    'fields' => ['foo'],
                ],
            ],
            [
                'fields' => [
                    'foo' => [],
                    'bar' => [
                        'exclude' => true,
                    ],
                    'baz' => [
                        'exclude' => true,
                    ],
                ],
            ],
            '{foo_legend},foo,bar;{bar_legend},baz',
        ];

        yield 'Adds the pid field for admins if it exists in the table' => [
            [
                -1 => [
                    'key' => '',
                    'class' => '',
                    'fields' => ['pid'],
                ],
                0 => [
                    'key' => 'foo_legend',
                    'class' => '',
                    'fields' => ['foo'],
                ],
            ],
            [
                'fields' => [
                    'foo' => [],
                ],
            ],
            '{foo_legend},foo',
            [],
            ['pid'],
            true,
        ];

        yield 'Does not add the pid field if user is not admin' => [
            [
                [
                    'key' => 'foo_legend',
                    'class' => '',
                    'fields' => ['foo'],
                ],
            ],
            [
                'fields' => [
                    'foo' => [],
                ],
            ],
            '{foo_legend},foo',
            [],
            ['pid'],
            false,
        ];

        yield 'Adds the sorting field for admins if it exists in the table' => [
            [
                -1 => [
                    'key' => '',
                    'class' => '',
                    'fields' => ['sorting'],
                ],
                0 => [
                    'key' => 'foo_legend',
                    'class' => '',
                    'fields' => ['foo'],
                ],
            ],
            [
                'fields' => [
                    'foo' => [],
                ],
            ],
            '{foo_legend},foo',
            [],
            ['sorting'],
            true,
        ];

        yield 'Does not add the sorting field if user is not admin' => [
            [
                [
                    'key' => 'foo_legend',
                    'class' => '',
                    'fields' => ['foo'],
                ],
            ],
            [
                'fields' => [
                    'foo' => [],
                ],
            ],
            '{foo_legend},foo',
            [],
            ['sorting'],
            false,
        ];

        yield 'Adds the pid and sorting fields for admins if they exists in the table' => [
            [
                -1 => [
                    'key' => '',
                    'class' => '',
                    'fields' => ['pid', 'sorting'],
                ],
                0 => [
                    'key' => 'foo_legend',
                    'class' => '',
                    'fields' => ['foo'],
                ],
            ],
            [
                'fields' => [
                    'foo' => [],
                ],
            ],
            '{foo_legend},foo',
            [],
            ['pid', 'sorting'],
            true,
        ];
    }

    private function mockRequestStackWithFieldsetState(array $fieldsetStates): RequestStack&MockObject
    {
        $sessionBag = $this->createMock(AttributeBag::class);
        $sessionBag
            ->expects($this->once())
            ->method('get')
            ->with('fieldset_states')
            ->willReturn($fieldsetStates)
        ;

        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->once())
            ->method('getBag')
            ->with('contao_backend')
            ->willReturn($sessionBag)
        ;

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->once())
            ->method('getSession')
            ->willReturn($session)
        ;

        return $requestStack;
    }

    private function mockConnectionWithTableColumns(array $tableColumns): Connection&MockObject
    {
        $schemaManager = $this->createMock(MySQLSchemaManager::class);
        $schemaManager
            ->method('listTableColumns')
            ->willReturn(array_flip($tableColumns))
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('createSchemaManager')
            ->willReturn($schemaManager)
        ;

        return $connection;
    }
}
