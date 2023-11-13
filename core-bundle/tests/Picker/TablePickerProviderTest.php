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

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Picker\PickerConfig;
use Contao\CoreBundle\Picker\TablePickerProvider;
use Contao\DC_Table;
use Contao\DcaLoader;
use Contao\TestCase\ContaoTestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use PHPUnit\Framework\MockObject\MockObject;
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

    /**
     * @dataProvider dcaAttributesProvider
     */
    public function testGetDcaAttributes(array $extra, string $value, array $expected): void
    {
        $provider = $this->createTableProvider();
        $config = new PickerConfig('', $extra, $value);

        $this->assertSame($expected, $provider->getDcaAttributes($config));
    }

    public function dcaAttributesProvider(): \Generator
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

    /**
     * @dataProvider menuItemsProvider
     */
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

    /**
     * @dataProvider menuItemsProvider
     */
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
            ->willReturn($this->createMock(ItemInterface::class))
        ;

        $config = $this->mockPickerConfig('tl_foobar', '', 'tablePicker.'.$current, $expectedCurrent);
        $provider = $this->createMenuTableProvider($modules, $current, $menu);

        $provider->createMenuItem($config);
    }

    public function menuItemsProvider(): \Generator
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
            $this->mockUnusedConnection(),
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
            $this->mockUnusedConnection(),
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
            $this->mockConnectionForQuery('tl_article', 15, ['pid' => 1]),
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
            'id' => '1',
        ];

        $config = $this->mockPickerConfig('tl_article', '42');

        $provider = $this->createTableProvider(
            $this->mockFrameworkWithDcaLoader('tl_article'),
            $this->mockRouterWithExpectedParams($params),
            $this->mockConnectionForQuery('tl_article', 42, ['pid' => 1]),
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
            $this->mockConnectionForQuery('tl_content', 2, ['pid' => 7, 'ptable' => 'tl_news'], true),
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
            $this->mockConnectionForQuery('tl_content', 15, ['pid' => 7, 'ptable' => ''], true),
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
            $this->mockConnectionForQuery('tl_article', 42, false),
        );

        $provider->getUrl($config);
    }

    public function testGetUrlAddsTableIfItsNotFirstInModule(): void
    {
        $GLOBALS['BE_MOD']['foo']['article'] = ['tables' => ['tl_article', 'tl_content']];
        $GLOBALS['TL_DCA']['tl_content'] = ['config' => ['dataContainer' => DC_Table::class, 'ptable' => 'tl_article']];

        $params = [
            'do' => 'article',
            'popup' => '1',
            'picker' => 'foobar',
        ];

        $config = $this->mockPickerConfig('tl_content');

        $provider = $this->createTableProvider(
            $this->mockFrameworkWithDcaLoader('tl_content'),
            $this->mockRouterWithExpectedParams($params),
            $this->mockUnusedConnection(),
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

    private function createTableProvider(ContaoFramework|null $framework = null, RouterInterface|null $router = null, Connection|null $connection = null): TablePickerProvider
    {
        return new TablePickerProvider(
            $framework ?: $this->createMock(ContaoFramework::class),
            $this->createMock(FactoryInterface::class),
            $router ?: $this->createMock(RouterInterface::class),
            $this->createMock(TranslatorInterface::class),
            $connection ?: $this->createMock(Connection::class),
        );
    }

    private function createMenuTableProvider(array $modules, string $current, ItemInterface|null $menu = null): TablePickerProvider
    {
        $expectedItems = [];
        $expectedParams = [];

        if ($menu) {
            $expectedItems[] = ['picker'];
        } else {
            $menu = $this->createMock(ItemInterface::class);
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
                    'linkAttributes' => ['class' => $module],
                    'current' => $current === $module,
                    'uri' => '',
                ],
            ];
        }

        $menuFactory = $this->createMock(FactoryInterface::class);
        $menuFactory
            ->expects($this->exactly(\count($expectedItems)))
            ->method('createItem')
            ->withConsecutive(...$expectedItems)
            ->willReturn($menu)
        ;

        return new TablePickerProvider(
            $this->createMock(ContaoFramework::class),
            $menuFactory,
            $this->mockRouterWithExpectedParams(...$expectedParams),
            $this->mockTranslatorWithExpectedCalls($modules),
            $this->createMock(Connection::class),
        );
    }

    private function mockPickerConfig(string $table = '', string $value = '', string $current = '', array|null $expectedCurrent = null): PickerConfig&MockObject
    {
        if (!$expectedCurrent && '' !== $current) {
            $expectedCurrent = [[$current]];
        }

        $config = $this->createMock(PickerConfig::class);
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

        $clone = $config
            ->method('cloneForCurrent')
        ;

        if ($expectedCurrent) {
            $clone->withConsecutive(...$expectedCurrent);
        }

        $clone->willReturnSelf();

        $config
            ->method('urlEncode')
            ->willReturn('foobar')
        ;

        return $config;
    }

    private function mockFrameworkWithDcaLoader(string $table): ContaoFramework&MockObject
    {
        $dcaLoader = $this->createMock(DcaLoader::class);
        $dcaLoader
            ->expects($this->once())
            ->method('load')
        ;

        $framework = $this->createMock(ContaoFramework::class);
        $framework
            ->expects($this->once())
            ->method('createInstance')
            ->with(DcaLoader::class, [$table])
            ->willReturn($dcaLoader)
        ;

        return $framework;
    }

    private function mockRouterWithExpectedParams(array ...$consecutive): RouterInterface&MockObject
    {
        $expected = [];

        foreach ($consecutive as $params) {
            $expected[] = ['contao_backend', $params];
        }

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->exactly(\count($expected)))
            ->method('generate')
            ->withConsecutive(...$expected)
            ->willReturn('')
        ;

        return $router;
    }

    private function mockUnusedConnection(): Connection&MockObject
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->never())
            ->method($this->anything())
        ;

        return $connection;
    }

    private function mockConnectionForQuery(string $table, int $id, array|false $data, bool $dynamicPtable = false): Connection&MockObject
    {
        $expr = $this->createMock(ExpressionBuilder::class);
        $expr
            ->expects($this->once())
            ->method('eq')
            ->with('id', $id)
            ->willReturnSelf()
        ;

        $result = $this->createMock(Result::class);
        $result
            ->expects($this->once())
            ->method('fetchAssociative')
            ->willReturn($data)
        ;

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder
            ->expects($this->once())
            ->method('expr')
            ->willReturn($expr)
        ;

        $queryBuilder
            ->expects($this->once())
            ->method('select')
            ->with('pid')
            ->willReturnSelf()
        ;

        $queryBuilder
            ->expects($this->once())
            ->method('from')
            ->with($table)
            ->willReturnSelf()
        ;

        $queryBuilder
            ->expects($this->once())
            ->method('where')
            ->with($expr)
            ->willReturnSelf()
        ;

        $queryBuilder
            ->expects($dynamicPtable ? $this->once() : $this->never())
            ->method('addSelect')
            ->with('ptable')
            ->willReturnSelf()
        ;

        $queryBuilder
            ->expects($this->once())
            ->method('executeQuery')
            ->willReturn($result)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder)
        ;

        return $connection;
    }

    private function mockTranslatorWithExpectedCalls(array $modules): TranslatorInterface&MockObject
    {
        $expected = [];

        foreach ($modules as $module) {
            $expected[] = ['MOD.'.$module.'.0', [], 'contao_default'];
        }

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->exactly(\count($modules)))
            ->method('trans')
            ->withConsecutive(...$expected)
            ->willReturnArgument(0)
        ;

        return $translator;
    }
}
