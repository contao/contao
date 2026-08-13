<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Picker;

use Contao\CoreBundle\DataContainer\DcaHierarchy;
use Contao\CoreBundle\Doctrine\DBAL\ParentTraversalOptions;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Picker\PickerConfig;
use Contao\CoreBundle\Picker\TablePickerProvider;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\DcaLoader;
use Contao\TestCase\ContaoTestCase;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class TablePickerProviderTest extends ContaoTestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($GLOBALS['TL_DCA'], $GLOBALS['BE_MOD']);
    }

    public function testName(): void
    {
        $provider = $this->createTableProvider();

        $this->assertSame('tablePicker', $provider->getName());
    }

    public function testSupportsContext(): void
    {
        $GLOBALS['TL_DCA']['tl_foobar']['config']['dataContainer'] = DC_Table::class;
        $GLOBALS['BE_MOD']['foo']['bar']['tables'] = ['tl_foobar'];

        $provider = $this->createTableProvider($this->mockFrameworkWithDcaLoader('tl_foobar'));

        $this->assertTrue($provider->supportsContext('dc.tl_foobar'));
    }

    public function testDoesNotSupportsContextWithoutPrefix(): void
    {
        $provider = $this->createTableProvider();

        $this->assertFalse($provider->supportsContext('foobar'));
    }

    public function testDoesNotSupportContextWithoutDataContainer(): void
    {
        $GLOBALS['TL_DCA']['tl_foobar']['config']['dataContainer'] = 'Foobar';
        $GLOBALS['BE_MOD']['foo']['bar']['tables'] = ['tl_foobar'];

        $provider = $this->createTableProvider($this->mockFrameworkWithDcaLoader('tl_foobar'));

        $this->assertFalse($provider->supportsContext('dc.tl_foobar'));
    }

    public function testDoesNotSupportContextWithoutModule(): void
    {
        $GLOBALS['TL_DCA']['tl_foobar']['config']['dataContainer'] = DC_Table::class;
        $GLOBALS['BE_MOD']['foo']['bar']['tables'] = ['tl_page'];

        $provider = $this->createTableProvider($this->mockFrameworkWithDcaLoader('tl_foobar'));

        $this->assertFalse($provider->supportsContext('dc.tl_foobar'));
    }

    public function testSupportsValue(): void
    {
        $provider = $this->createTableProvider();

        $this->assertTrue($provider->supportsValue($this->mockPickerConfig()));
    }

    public function testIsCurrent(): void
    {
        $provider = $this->createTableProvider();
        $config = $this->mockPickerConfig('', '', 'tablePicker.article');

        $this->assertTrue($provider->isCurrent($config));

        $config = $this->mockPickerConfig('', '', 'fooBar.article');

        $this->assertFalse($provider->isCurrent($config));
    }

    public function testGetDcaTableFromContext(): void
    {
        $config = $this->mockPickerConfig('tl_content');
        $provider = $this->createTableProvider();

        $this->assertSame('tl_content', $provider->getDcaTable($config));
    }

    public function testGetDcaTableFromEmptyContext(): void
    {
        $provider = $this->createTableProvider();

        $this->assertSame('', $provider->getDcaTable());
    }

    public function testConvertDcaValueToInteger(): void
    {
        $provider = $this->createTableProvider();
        $config = $this->mockPickerConfig();

        $this->assertSame(15, $provider->convertDcaValue($config, '15'));
        $this->assertSame(0, $provider->convertDcaValue($config, []));
    }

    #[DataProvider('dcaAttributesProvider')]
    public function testGetDcaAttributes(array $extra, string $value, array $expected): void
    {
        $provider = $this->createTableProvider();
        $config = new PickerConfig('', $extra, $value);

        $this->assertSame($expected, $provider->getDcaAttributes($config));
    }

    public static function dcaAttributesProvider(): iterable
    {
        yield 'default fieldtype radio' => [
            [],
            '',
            ['fieldType' => 'radio'],
        ];

        yield 'single value' => [
            [],
            '15',
            ['fieldType' => 'radio', 'value' => [15]],
        ];

        yield 'multiple values' => [
            [],
            '15,10,3',
            ['fieldType' => 'radio', 'value' => [15, 10, 3]],
        ];

        yield 'field type' => [
            ['fieldType' => 'checkbox'],
            '',
            ['fieldType' => 'checkbox'],
        ];

        yield 'preserve source record' => [
            ['source' => '15'],
            '',
            ['fieldType' => 'radio'],
        ];

        yield 'everything' => [
            ['fieldType' => 'foobar', 'source' => '42'],
            '',
            ['fieldType' => 'foobar'],
        ];

        yield 'ignores additional extras' => [
            ['foo' => 'bar'],
            '',
            ['fieldType' => 'radio'],
        ];
    }

    #[DataProvider('menuItemsProvider')]
    public function testAddMenuItems(array $modules, string $current): void
    {
        $expectedCurrent = [];

        foreach ($modules as $module) {
            $GLOBALS['BE_MOD']['foo'][$module]['tables'] = ['tl_foobar'];
            $expectedCurrent[] = ['tablePicker.'.$module];
        }

        $config = $this->mockPickerConfig('tl_foobar', '', 'tablePicker.'.$current, $expectedCurrent);
        $provider = $this->createMenuTableProvider($modules, $current);

        $menu = $this->createMock(ItemInterface::class);
        $menu
            ->expects($this->exactly(\count($modules)))
            ->method('addChild')
        ;

        $provider->addMenuItems($menu, $config);
    }

    #[DataProvider('menuItemsProvider')]
    public function testCreateMenuItem(array $modules, string $current): void
    {
        $expectedCurrent = [];

        foreach ($modules as $module) {
            $GLOBALS['BE_MOD']['foo'][$module]['tables'] = ['tl_foobar'];
            $expectedCurrent[] = ['tablePicker.'.$module];
        }

        $menu = $this->createMock(ItemInterface::class);
        $menu
            ->expects($this->exactly(\count($modules)))
            ->method('addChild')
        ;

        $menu
            ->expects($this->once())
            ->method('getFirstChild')
            ->willReturn($this->createStub(ItemInterface::class))
        ;

        $config = $this->mockPickerConfig('tl_foobar', '', 'tablePicker.'.$current, $expectedCurrent);

        $provider = $this->createMenuTableProvider($modules, $current, $menu);
        $provider->createMenuItem($config);
    }

    public static function menuItemsProvider(): iterable
    {
        yield 'one module without current' => [['article'], ''];
        yield 'one module with current' => [['article'], 'article'];
        yield 'multiple modules without current' => [['article', 'news'], ''];
        yield 'multiple modules with first as current' => [['article', 'news'], 'article'];
        yield 'multiple modules with second as current' => [['article', 'news'], 'news'];
    }

    public function testGetUrlWithoutValue(): void
    {
        $GLOBALS['BE_MOD']['foo']['article'] = ['tables' => ['tl_article']];
        $GLOBALS['TL_DCA']['tl_article'] = ['config' => ['dataContainer' => DC_Table::class]];

        $params = [
            'do' => 'article',
            'popup' => '1',
            'picker' => 'foobar',
        ];

        $config = $this->mockPickerConfig('tl_article');

        $provider = $this->createTableProvider(
            $this->mockFrameworkWithDcaLoader('tl_article'),
            $this->mockRouterWithExpectedParams($params),
            $this->mockUnusedHierarchy(),
        );

        $provider->getUrl($config);
    }

    public function testGetUrlWithoutPtable(): void
    {
        $GLOBALS['BE_MOD']['foo']['article'] = ['tables' => ['tl_article']];
        $GLOBALS['TL_DCA']['tl_article'] = ['config' => ['dataContainer' => DC_Table::class]];

        $params = [
            'do' => 'article',
            'popup' => '1',
            'picker' => 'foobar',
        ];

        $config = $this->mockPickerConfig('tl_article', '15');

        $provider = $this->createTableProvider(
            $this->mockFrameworkWithDcaLoader('tl_article'),
            $this->mockRouterWithExpectedParams($params),
            $this->mockHierarchyForQuery('tl_article', 15, ['id' => 15]),
        );

        $provider->getUrl($config);
    }

    public function testGetUrlWithPtable(): void
    {
        $GLOBALS['BE_MOD']['foo']['article'] = ['tables' => ['tl_article']];
        $GLOBALS['TL_DCA']['tl_article'] = ['config' => ['dataContainer' => DC_Table::class, 'ptable' => 'tl_page']];

        $params = [
            'do' => 'article',
            'popup' => '1',
            'picker' => 'foobar',
        ];

        $config = $this->mockPickerConfig('tl_article', '15');

        $provider = $this->createTableProvider(
            $this->mockFrameworkWithDcaLoader('tl_article'),
            $this->mockRouterWithExpectedParams($params),
            $this->mockHierarchyForQuery('tl_article', 15, ['id' => 15, 'pid' => 1]),
        );

        $provider->getUrl($config);
    }

    public function testGetUrlWithPtableAndMultipleTables(): void
    {
        $GLOBALS['BE_MOD']['foo']['article'] = ['tables' => ['tl_page', 'tl_article']];
        $GLOBALS['TL_DCA']['tl_article'] = ['config' => ['dataContainer' => DC_Table::class, 'ptable' => 'tl_page']];

        $params = [
            'do' => 'article',
            'popup' => '1',
            'picker' => 'foobar',
            'table' => 'tl_article',
            'id' => 1,
        ];

        $config = $this->mockPickerConfig('tl_article', '42');

        $provider = $this->createTableProvider(
            $this->mockFrameworkWithDcaLoader('tl_article'),
            $this->mockRouterWithExpectedParams($params),
            $this->mockHierarchyForQuery('tl_article', 42, ['id' => 42, 'pid' => 1]),
        );

        $provider->getUrl($config);
    }

    public function testGetUrlWithDynamicPtable(): void
    {
        $GLOBALS['BE_MOD']['foo']['article'] = ['tables' => ['tl_article', 'tl_content']];
        $GLOBALS['BE_MOD']['foo']['news'] = ['tables' => ['tl_news', 'tl_content']];

        $GLOBALS['TL_DCA']['tl_content'] = [
            'config' => [
                'dataContainer' => DC_Table::class,
                'ptable' => 'tl_article',
                'dynamicPtable' => true,
            ],
        ];

        $params = [
            'do' => 'news',
            'popup' => '1',
            'picker' => 'foobar',
            'table' => 'tl_content',
            'id' => 7,
        ];

        $config = $this->mockPickerConfig('tl_content', '2');

        $provider = $this->createTableProvider(
            $this->mockFrameworkWithDcaLoader('tl_content'),
            $this->mockRouterWithExpectedParams($params),
            $this->mockHierarchyForQuery('tl_content', 2, ['pid' => 7, 'ptable' => 'tl_news'], 'tl_news'),
        );

        $provider->getUrl($config);
    }

    public function testGetUrlWithNestedDynamicPtable(): void
    {
        $GLOBALS['BE_MOD']['foo']['article'] = ['tables' => ['tl_article', 'tl_content']];
        $GLOBALS['BE_MOD']['foo']['news'] = ['tables' => ['tl_news', 'tl_content']];

        $GLOBALS['TL_DCA']['tl_content'] = [
            'config' => [
                'dataContainer' => DC_Table::class,
                'ptable' => 'tl_article',
                'dynamicPtable' => true,
            ],
        ];

        $params = [
            'do' => 'news',
            'popup' => '1',
            'picker' => 'foobar',
            'table' => 'tl_content',
            'ptable' => 'tl_content',
            'id' => 7,
        ];

        $config = $this->mockPickerConfig('tl_content', '2');

        $provider = $this->createTableProvider(
            $this->mockFrameworkWithDcaLoader('tl_content'),
            $this->mockRouterWithExpectedParams($params),
            $this->mockHierarchyForQuery('tl_content', 2, ['pid' => 7, 'ptable' => 'tl_content'], 'tl_news'),
        );

        $provider->getUrl($config);
    }

    public function testGetUrlWithEmptyDynamicPtable(): void
    {
        $GLOBALS['BE_MOD']['foo']['article'] = ['tables' => ['tl_article', 'tl_content']];
        $GLOBALS['BE_MOD']['foo']['news'] = ['tables' => ['tl_news', 'tl_content']];
        $GLOBALS['TL_DCA']['tl_content'] = ['config' => ['dataContainer' => DC_Table::class, 'dynamicPtable' => true]];

        $params = [
            'do' => 'article',
            'popup' => '1',
            'picker' => 'foobar',
            'table' => 'tl_content',
            'id' => 7,
        ];

        $config = $this->mockPickerConfig('tl_content', '15');

        $provider = $this->createTableProvider(
            $this->mockFrameworkWithDcaLoader('tl_content'),
            $this->mockRouterWithExpectedParams($params),
            $this->mockHierarchyForQuery('tl_content', 15, ['pid' => 7, 'ptable' => ''], ''),
        );

        $provider->getUrl($config);
    }

    public function testGetUrlWithoutDbRecordRendersFirstModule(): void
    {
        $GLOBALS['BE_MOD']['foo']['article'] = ['tables' => ['tl_article']];
        $GLOBALS['TL_DCA']['tl_article'] = ['config' => ['dataContainer' => DC_Table::class, 'ptable' => 'tl_page']];

        $params = [
            'do' => 'article',
            'popup' => '1',
            'picker' => 'foobar',
        ];

        $config = $this->mockPickerConfig('tl_article', '42');

        $provider = $this->createTableProvider(
            $this->mockFrameworkWithDcaLoader('tl_article'),
            $this->mockRouterWithExpectedParams($params),
            $this->mockHierarchyForQuery('tl_article', 42, false),
        );

        $provider->getUrl($config);
    }

    public function testGetUrlAddsTableIfItsNotFirstInModule(): void
    {
        $GLOBALS['BE_MOD']['foo']['article'] = ['tables' => ['tl_article', 'tl_content']];

        $GLOBALS['TL_DCA']['tl_content'] = [
            'config' => ['dataContainer' => DC_Table::class, 'ptable' => 'tl_article'],
            'list' => ['sorting' => ['mode' => DataContainer::MODE_PARENT]],
        ];

        $params = [
            'do' => 'article',
            'popup' => '1',
            'picker' => 'foobar',
        ];

        $config = $this->mockPickerConfig('tl_content');

        $provider = $this->createTableProvider(
            $this->mockFrameworkWithDcaLoader('tl_content', 'tl_article'),
            $this->mockRouterWithExpectedParams($params),
            $this->mockUnusedHierarchy(),
        );

        $provider->getUrl($config);
    }

    public function testGetUrlDoesNotAddTableForDynamicPtable(): void
    {
        $GLOBALS['BE_MOD']['foo']['article'] = ['tables' => ['tl_article', 'tl_content']];

        $GLOBALS['TL_DCA']['tl_content'] = [
            'config' => ['dataContainer' => DC_Table::class, 'dynamicPtable' => true],
            'list' => ['sorting' => ['mode' => DataContainer::MODE_PARENT]],
        ];

        $params = [
            'do' => 'article',
            'popup' => '1',
            'picker' => 'foobar',
        ];

        $config = $this->mockPickerConfig('tl_content');

        $provider = $this->createTableProvider(
            $this->mockFrameworkWithDcaLoader('tl_content', 'tl_article'),
            $this->mockRouterWithExpectedParams($params),
            $this->mockUnusedHierarchy(),
        );

        $provider->getUrl($config);
    }

    public function testGetUrlAddsTableForNonParentMode(): void
    {
        $GLOBALS['BE_MOD']['foo']['article'] = ['tables' => ['tl_article', 'tl_content']];

        $GLOBALS['TL_DCA']['tl_content'] = [
            'config' => ['dataContainer' => DC_Table::class],
            'list' => ['sorting' => ['mode' => DataContainer::MODE_SORTED]],
        ];

        $params = [
            'do' => 'article',
            'popup' => '1',
            'picker' => 'foobar',
            'table' => 'tl_content',
        ];

        $config = $this->mockPickerConfig('tl_content');

        $provider = $this->createTableProvider(
            $this->mockFrameworkWithDcaLoader('tl_content', 'tl_article'),
            $this->mockRouterWithExpectedParams($params),
            $this->mockUnusedHierarchy(),
        );

        $provider->getUrl($config);
    }

    public function testGetUrlAddsTopmostParentTable(): void
    {
        $GLOBALS['BE_MOD']['foo']['article'] = ['tables' => ['tl_foo', 'tl_parent', 'tl_child', 'tl_grandchild']];

        $GLOBALS['TL_DCA']['tl_grandchild'] = [
            'config' => ['dataContainer' => DC_Table::class, 'ptable' => 'tl_child'],
            'list' => ['sorting' => ['mode' => DataContainer::MODE_PARENT]],
        ];

        $GLOBALS['TL_DCA']['tl_child'] = [
            'config' => ['dataContainer' => DC_Table::class, 'ptable' => 'tl_parent'],
            'list' => ['sorting' => ['mode' => DataContainer::MODE_PARENT]],
        ];

        $GLOBALS['TL_DCA']['tl_parent'] = [
            'config' => ['dataContainer' => DC_Table::class],
            'list' => ['sorting' => ['mode' => DataContainer::MODE_SORTED]],
        ];

        $params = [
            'do' => 'article',
            'popup' => '1',
            'picker' => 'foobar',
            'table' => 'tl_parent',
        ];

        $config = $this->mockPickerConfig('tl_grandchild', '123');

        $provider = $this->createTableProvider(
            $this->mockFrameworkWithDcaLoader('tl_parent', 'tl_child', 'tl_grandchild'),
            $this->mockRouterWithExpectedParams($params),
            $this->mockHierarchyForQuery('tl_grandchild', 123, false),
        );

        $provider->getUrl($config);
    }

    public function testThrowsExceptionIfTableIsNotInAnyModule(): void
    {
        $config = new PickerConfig('tl_foobar');
        $provider = $this->createTableProvider();

        $this->expectException(\RuntimeException::class);

        $provider->getUrl($config);
    }

    private function createTableProvider(ContaoFramework|null $framework = null, RouterInterface|null $router = null, DcaHierarchy|null $dcaHierarchy = null): TablePickerProvider
    {
        return new TablePickerProvider(
            $framework ?: $this->createStub(ContaoFramework::class),
            $this->createStub(FactoryInterface::class),
            $router ?: $this->createStub(RouterInterface::class),
            $this->createStub(TranslatorInterface::class),
            $dcaHierarchy ?: $this->createStub(DcaHierarchy::class),
        );
    }

    private function createMenuTableProvider(array $modules, string $current, ItemInterface|null $menu = null): TablePickerProvider
    {
        $expectedItems = [];
        $expectedParams = [];

        if ($menu) {
            $expectedItems[] = ['picker', []];
        } else {
            $menu = $this->createStub(ItemInterface::class);
        }

        foreach ($modules as $module) {
            $expectedParams[] = [
                'do' => $module,
                'popup' => '1',
                'picker' => 'foobar',
            ];

            $expectedItems[] = [
                $module,
                [
                    'label' => 'MOD.'.$module.'.0',
                    'linkAttributes' => ['class' => $module.'Picker'],
                    'current' => $current === $module,
                    'uri' => '',
                ],
            ];
        }

        $matcher = $this->exactly(\count($expectedItems));

        $menuFactory = $this->createMock(FactoryInterface::class);
        $menuFactory
            ->expects($matcher)
            ->method('createItem')
            ->with($this->callback(
                static fn (...$parameters) => $expectedItems[$matcher->numberOfInvocations() - 1] === $parameters,
            ))
            ->willReturn($menu)
        ;

        return new TablePickerProvider(
            $this->createStub(ContaoFramework::class),
            $menuFactory,
            $this->mockRouterWithExpectedParams(...$expectedParams),
            $this->mockTranslatorWithExpectedCalls($modules),
            $this->createStub(DcaHierarchy::class),
        );
    }

    private function mockPickerConfig(string $table = '', string $value = '', string $current = '', array|null $expectedCurrent = null): PickerConfig&Stub
    {
        if (!$expectedCurrent && '' !== $current) {
            $expectedCurrent = [[$current]];
        }

        $config = $expectedCurrent ? $this->createMock(PickerConfig::class) : $this->createStub(PickerConfig::class);
        $config
            ->method('getContext')
            ->willReturn('dc.'.$table)
        ;

        $config
            ->method('getValue')
            ->willReturn($value)
        ;

        $config
            ->method('getCurrent')
            ->willReturn($current)
        ;

        $clone = $config->method('cloneForCurrent');

        if ($expectedCurrent) {
            $clone->with($this->callback(
                static function (...$parameters) use (&$expectedCurrent) {
                    $pos = array_search($parameters, $expectedCurrent, true);
                    unset($expectedCurrent[$pos]);

                    return false !== $pos;
                },
            ));
        }

        $clone->willReturnSelf();

        $config
            ->method('urlEncode')
            ->willReturn('foobar')
        ;

        return $config;
    }

    private function mockFrameworkWithDcaLoader(string ...$tables): ContaoFramework&MockObject
    {
        $dcaLoader = $this->createMock(DcaLoader::class);
        $dcaLoader
            ->expects($this->atLeastOnce())
            ->method('load')
        ;

        $framework = $this->createMock(ContaoFramework::class);
        $framework
            ->expects($this->atLeastOnce())
            ->method('createInstance')
            ->with(
                DcaLoader::class,
                $this->callback(static fn (array $args) => \in_array($args[0], $tables, true)),
            )
            ->willReturn($dcaLoader)
        ;

        return $framework;
    }

    private function mockRouterWithExpectedParams(array ...$consecutive): RouterInterface&MockObject
    {
        $expected = [];

        foreach ($consecutive as $params) {
            $expected[] = ['contao_backend', $params, UrlGeneratorInterface::ABSOLUTE_PATH];
        }

        $matcher = $this->exactly(\count($expected));

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($matcher)
            ->method('generate')
            ->with($this->callback(
                static fn (...$parameters) => $expected[$matcher->numberOfInvocations() - 1] === $parameters,
            ))
            ->willReturn('')
        ;

        return $router;
    }

    private function mockUnusedHierarchy(): DcaHierarchy&MockObject
    {
        $dcaHierarchy = $this->createMock(DcaHierarchy::class);
        $dcaHierarchy
            ->expects($this->never())
            ->method($this->anything())
        ;

        return $dcaHierarchy;
    }

    private function mockHierarchyForQuery(string $table, int $id, array|false $data, string|null $dynamicPtable = null): DcaHierarchy&MockObject
    {
        $dcaHierarchy = $this->createMock(DcaHierarchy::class);
        $dcaHierarchy
            ->expects($this->once())
            ->method('getParentRows')
            ->with(
                $id,
                $table,
                $this->callback(static fn (ParentTraversalOptions $options): bool => $options->includesBoundaryRow()
                    && 1 === $options->maxDepth()
                    && (null === $dynamicPtable ? [] : ['ptable']) === $options->columns()),
            )
            ->willReturn(false === $data ? [] : [[...$data, 'id' => $data['id'] ?? $id, 'pid' => $data['pid'] ?? 0]])
        ;

        if (null !== $dynamicPtable) {
            $expectation = $dcaHierarchy
                ->expects($this->once())
                ->method('getParentTableAndId')
                ->with($id, $table)
            ;

            '' === $dynamicPtable
                ? $expectation
                    ->willThrowException(new \RuntimeException())
                : $expectation
                    ->willReturn([$dynamicPtable, 0])
            ;
        }

        return $dcaHierarchy;
    }

    private function mockTranslatorWithExpectedCalls(array $modules): TranslatorInterface&MockObject
    {
        $expected = [];

        foreach ($modules as $module) {
            $expected[] = ['MOD.'.$module.'.0', [], 'contao_default', null];
        }

        $matcher = $this->exactly(\count($modules));

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($matcher)
            ->method('trans')
            ->with($this->callback(
                static fn (...$parameters) => $expected[$matcher->numberOfInvocations() - 1] === $parameters,
            ))
            ->willReturnArgument(0)
        ;

        return $translator;
    }
}
