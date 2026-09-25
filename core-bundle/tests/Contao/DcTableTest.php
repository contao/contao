<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Contao;

use Contao\CoreBundle\DataContainer\VirtualFieldsHandler;
use Contao\CoreBundle\Session\Attribute\ArrayAttributeBag;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\Input;
use Contao\System;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class DcTableTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA']);

        $this->resetStaticProperties([System::class, DataContainer::class, Input::class]);

        parent::tearDown();
    }

    #[DataProvider('getPalette')]
    public function testGetPalette(array $dca, array $row, string $expected): void
    {
        $result = $this->createMock(Result::class);
        $result
            ->expects($this->once())
            ->method('iterateAssociative')
            ->willReturn(new \ArrayObject([$row]))
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('executeQuery')
            ->with('SELECT * FROM tl_test WHERE id IN (?)', [[1]])
            ->willReturn($result)
        ;

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('isGranted')
            ->willReturn(true)
        ;

        $virtualFieldsHandler = $this->createMock(VirtualFieldsHandler::class);
        $virtualFieldsHandler
            ->expects($this->once())
            ->method('expandFields')
            ->willReturnCallback(
                static fn (array $record) => $record,
            )
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('database_connection', $connection);
        $container->set('security.helper', $security);
        $container->set('contao.data_container.virtual_fields_handler', $virtualFieldsHandler);

        System::setContainer($container);

        $reflection = new \ReflectionClass(DC_Table::class);
        $dataContainer = $reflection->newInstanceWithoutConstructor();

        $id = $reflection->getProperty('intId');
        $id->setValue($dataContainer, 1);

        $table = $reflection->getProperty('strTable');
        $table->setValue($dataContainer, 'tl_test');

        $GLOBALS['TL_DCA']['tl_test'] = $dca;

        $this->assertSame($expected, $dataContainer->getPalette());
    }

    public static function getPalette(): iterable
    {
        yield [
            [
                'palettes' => [
                    '__selector__' => ['fieldA', 'fieldB', 'fieldC'],
                    'default' => 'paletteDefault',
                    'valueA' => 'paletteA',
                    'valueAvalueC' => 'paletteAC',
                ],
                'fields' => [
                    'fieldA' => ['inputType' => 'text'],
                    'fieldB' => ['inputType' => 'text'],
                    'fieldC' => ['inputType' => 'text'],
                ],
            ],
            [
                'id' => 1,
                'fieldA' => 'valueA',
                'fieldB' => null,
                'fieldC' => 'valueC',
            ],
            'paletteAC',
        ];
    }

    #[DataProvider('provideTreeLimits')]
    public function testSelectionRespectsTheApiTreeLimit(bool $api, int|null $configuredLimit, int $expected): void
    {
        $dca = $GLOBALS['TL_DCA'] ?? null;
        $config = $GLOBALS['TL_CONFIG'] ?? null;

        $request = Request::create('/contao?act=select');
        $request->attributes->set('_contao_api', $api);

        $container = new ContainerBuilder();
        $container->set('request_stack', new RequestStack([$request]));
        System::setContainer($container);

        $GLOBALS['TL_CONFIG']['maxResultsPerPage'] = 2;
        $GLOBALS['TL_DCA']['tl_test']['list']['sorting']['treeRecordLimit'] = $configuredLimit;

        $dc = new class() extends DC_Table {
            public function __construct()
            {
                $this->strTable = 'tl_test';
            }

            public function renderNextRecord(): bool
            {
                if (!$this->canRenderTreeRecord()) {
                    return false;
                }

                $this->countTreeRecord();

                return true;
            }
        };

        try {
            $rendered = 0;

            for ($i = 0; $i < 5; ++$i) {
                $rendered += (int) $dc->renderNextRecord();
            }

            $this->assertSame($expected, $rendered);
        } finally {
            $this->restoreGlobals($dca, $config);
            $this->resetStaticProperties([Input::class, System::class]);
        }
    }

    public static function provideTreeLimits(): iterable
    {
        yield 'API uses the DCA limit' => [true, 3, 3];
        yield 'API uses the configured fallback' => [true, null, 2];
        yield 'API respects an explicitly unlimited tree' => [true, 0, 5];
        yield 'backend selection remains unlimited' => [false, 3, 5];
    }

    #[DataProvider('provideSortingChoices')]
    public function testApiSortingUsesTheBackendOrder(string|null $sort, int $flag, array $expected): void
    {
        $dc = $this->createSortingDataContainer($sort);
        $GLOBALS['TL_DCA']['tl_test']['fields']['title']['flag'] = $flag;

        $this->assertSame($expected, json_decode($dc->showAll(), true, flags: JSON_THROW_ON_ERROR));
    }

    public static function provideSortingChoices(): iterable
    {
        yield 'default leaves callbacks intact' => [null, DataContainer::SORT_BOTH, ['callback DESC']];
        yield 'ascending choice' => ['title ASC', DataContainer::SORT_BOTH, ['title ASC', 'alias', 'id']];
        yield 'descending choice' => ['title DESC', DataContainer::SORT_BOTH, ['title DESC', 'alias', 'id']];
        yield 'implicit ascending' => ['title', DataContainer::SORT_ASC, ['title', 'alias', 'id']];
        yield 'implicit descending' => ['title', DataContainer::SORT_DESC, ['title', 'alias', 'id']];
        yield 'date descending' => ['title', DataContainer::SORT_DAY_DESC, ['title', 'alias', 'id']];
    }

    #[DataProvider('provideUnavailableSorting')]
    public function testApiRejectsUnavailableSorting(string $sort, array $sorting, int $flag): void
    {
        $dc = $this->createSortingDataContainer($sort);
        $GLOBALS['TL_DCA']['tl_test']['list']['sorting'] = $sorting;
        $GLOBALS['TL_DCA']['tl_test']['fields']['title']['flag'] = $flag;
        $this->expectException(UnprocessableEntityHttpException::class);

        $dc->showAll();
    }

    public static function provideUnavailableSorting(): iterable
    {
        $sorting = ['mode' => DataContainer::MODE_SORTABLE, 'panelLayout' => 'sort'];

        yield 'unknown field' => ['password', $sorting, DataContainer::SORT_BOTH];
        yield 'SQL expression' => ['title DESC, id', $sorting, DataContainer::SORT_BOTH];
        yield 'unavailable direction' => ['title DESC', $sorting, DataContainer::SORT_ASC];
        yield 'direction required' => ['title', $sorting, DataContainer::SORT_BOTH];
        yield 'no panel' => ['title ASC', ['mode' => DataContainer::MODE_SORTABLE], DataContainer::SORT_BOTH];
        yield 'fixed sorting' => ['title ASC', ['mode' => DataContainer::MODE_SORTED, 'panelLayout' => 'sort'], DataContainer::SORT_BOTH];
        yield 'tree' => ['title ASC', ['mode' => DataContainer::MODE_TREE, 'panelLayout' => 'sort'], DataContainer::SORT_BOTH];
    }

    public function testApiSortingSupportsTheParentView(): void
    {
        $dc = $this->createSortingDataContainer('title DESC');
        $GLOBALS['TL_DCA']['tl_test']['list']['sorting']['mode'] = DataContainer::MODE_PARENT;

        $this->assertSame(['title DESC', 'alias', 'id'], json_decode($dc->showAll(), true, flags: JSON_THROW_ON_ERROR));
    }

    public function testApiPaginationDoesNotReadTheBackendSession(): void
    {
        $dc = $this->createSortingDataContainer(null);
        $method = new \ReflectionMethod($dc, 'paginationMenu');

        $this->assertSame('', $method->invoke($dc));
    }

    private function createSortingDataContainer(string|null $sort): DC_Table
    {
        $request = new Request(attributes: ['_contao_api' => true]);
        $bag = new ArrayAttributeBag('_contao_be_attributes');
        $bag->setName('contao_backend');

        $session = new Session(new MockArraySessionStorage());
        $session->registerBag($bag);
        $session->start();

        if (null !== $sort) {
            $bag->set('sorting', ['tl_test' => $sort]);
        }

        $request->setSession($session);
        $stack = new RequestStack();
        $stack->push($request);

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('request_stack', $stack);
        System::setContainer($container);
        $GLOBALS['TL_DCA']['tl_test'] = [
            'list' => ['sorting' => ['mode' => DataContainer::MODE_SORTABLE, 'panelLayout' => 'sort', 'fields' => ['title', 'alias', 'id']]],
            'fields' => ['title' => ['sorting' => true, 'flag' => DataContainer::SORT_BOTH]],
        ];

        return new class() extends DC_Table {
            public function __construct()
            {
                $this->strTable = 'tl_test';
                $this->orderBy = ['callback DESC'];
            }

            protected function reviseTable(): void
            {
            }

            protected function panel(): string
            {
                return $this->sortMenu();
            }

            protected function listView(): string
            {
                return json_encode($this->orderBy, JSON_THROW_ON_ERROR);
            }

            protected function parentView(): string
            {
                return $this->listView();
            }

            protected function render(string $component, array $parameters): string
            {
                return $parameters['view'];
            }
        };
    }

    private function restoreGlobals(array|null $dca, array|null $config): void
    {
        unset($GLOBALS['TL_DCA'], $GLOBALS['TL_CONFIG']);

        if (null !== $dca) {
            $GLOBALS['TL_DCA'] = $dca;
        }

        if (null !== $config) {
            $GLOBALS['TL_CONFIG'] = $config;
        }
    }
}
