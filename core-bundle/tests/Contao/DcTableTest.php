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
use Contao\CoreBundle\Exception\ResponseException;
use Contao\CoreBundle\Session\Attribute\ArrayAttributeBag;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Database;
use Contao\Database\Statement;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\Input;
use Contao\System;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class DcTableTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA'], $GLOBALS['TL_LANG']);

        $this->resetStaticProperties([System::class, DataContainer::class, Database::class, Input::class]);

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

    #[DataProvider('provideUndoFailures')]
    public function testUndoTransaction(string|null $failure): void
    {
        $operations = [];
        $database = $this->createStub(Database::class);

        $statement = $this->createMock(Statement::class);
        $statement
            ->method('set')
            ->willReturnSelf()
        ;

        $statement
            ->expects($this->exactly(null === $failure || 'delete' === $failure ? 1 : 0))
            ->method('limit')
            ->willReturnSelf()
        ;

        $statement
            ->expects($this->exactly('insert' === $failure ? 1 : 2))
            ->method('__get')
            ->with('affectedRows')
            ->willReturnOnConsecutiveCalls(1, 'no affected rows' === $failure ? 0 : 1)
        ;

        $executions = 0;

        $statement
            ->expects($this->exactly(null === $failure || 'delete' === $failure ? 3 : 2))
            ->method('execute')
            ->willReturnCallback(
                static function () use ($statement, $failure, &$executions): Statement {
                    if (2 === ++$executions && 'insert' === $failure) {
                        throw new \RuntimeException('Insert failed.');
                    }

                    if (3 === $executions && 'delete' === $failure) {
                        throw new \RuntimeException('Delete failed.');
                    }

                    return $statement;
                },
            )
        ;

        foreach (['beginTransaction', 'commitTransaction', 'rollbackTransaction'] as $method) {
            $database
                ->method($method)
                ->willReturnCallback(
                    static function () use (&$operations, $method): void {
                        $operations[] = $method;
                    },
                )
            ;
        }

        $database
            ->method('getFieldNames')
            ->willReturn(['id'])
        ;

        $database
            ->method('prepare')
            ->willReturnCallback(
                static function (string $query) use ($statement, &$operations): Statement {
                    $operations[] = $query;

                    return $statement;
                },
            )
        ;

        $property = new \ReflectionProperty(Database::class, 'objInstance');
        $property->setValue(null, $database);

        $session = $this->mockSession();
        $this->assertInstanceOf(FlashBagAwareSessionInterface::class, $session);

        $request = new Request();
        $request->attributes->set('_scope', 'backend');
        $request->setSession($session);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(null === $failure ? $this->once() : $this->never())
            ->method('info')
        ;

        $logger
            ->expects(null === $failure ? $this->never() : $this->once())
            ->method('error')
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('request_stack', $requestStack);
        $container->set('contao.routing.scope_matcher', $this->mockScopeMatcher());
        $container->set('monolog.logger.contao.general', $logger);
        $container->set('monolog.logger.contao.error', $logger);
        System::setContainer($container);

        $GLOBALS['TL_LANG']['ERR']['undoNotRestored'] = 'Restore failed.';

        $GLOBALS['TL_DCA']['tl_undo_test']['config']['onundo_callback'] = [static function (string $table, array $row) use ($failure): void {
            if ('callback' === $failure && 2 === $row['id']) {
                throw new \Error('Callback failed.');
            }
        }];

        $driver = new class() extends DC_Table {
            public function __construct()
            {
                $this->strTable = 'tl_undo';
                $this->intId = 1;
            }

            /**
             * @phpstan-ignore return.unusedType
             */
            public function getCurrentRecord(int|string|null $id = null, string|null $table = null): array|null
            {
                return ['query' => 'DELETE FROM tl_undo_test', 'data' => serialize(['tl_undo_test' => [['id' => 1], ['id' => 2]]])];
            }

            public static function loadDataContainer($strTable): void
            {
            }

            public static function getReferer($blnEncodeAmpersands = false, $strTable = null, $intLevel = 1)
            {
                return '/contao?do=undo';
            }

            public static function redirect($strLocation, $intStatus = 303): never
            {
                throw new ResponseException(new RedirectResponse($strLocation, $intStatus));
            }

            public function invalidateCacheTags(): void
            {
            }
        };

        try {
            $driver->undo();
            $this->fail('Undo must redirect back to the list.');
        } catch (ResponseException $exception) {
            $this->assertSame('/contao?do=undo', $exception->getResponse()->headers->get('Location'));
        }

        $expectedOperations = ['beginTransaction', 'INSERT INTO tl_undo_test %s', 'INSERT INTO tl_undo_test %s'];

        if (null === $failure || 'delete' === $failure) {
            $expectedOperations[] = 'DELETE FROM tl_undo WHERE id=?';
        }

        $expectedOperations[] = null === $failure ? 'commitTransaction' : 'rollbackTransaction';
        $this->assertSame($expectedOperations, $operations);
        $this->assertSame(null === $failure ? [] : ['Restore failed.'], $session->getFlashBag()->get('contao.BE.error'));
    }

    public static function provideUndoFailures(): iterable
    {
        yield 'success' => [null];
        yield 'insert failure' => ['insert'];
        yield 'callback failure' => ['callback'];
        yield 'no affected rows' => ['no affected rows'];
        yield 'delete failure' => ['delete'];
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

        $container = new ContainerBuilder();
        $container->set('request_stack', new RequestStack([$request]));
        System::setContainer($container);

        $GLOBALS['TL_CONFIG']['maxResultsPerPage'] = 2;
        $GLOBALS['TL_DCA']['tl_test']['list']['sorting']['treeRecordLimit'] = $configuredLimit;

        $dc = new class($api) extends DC_Table {
            public function __construct(bool $api)
            {
                $this->strTable = 'tl_test';
                $this->setApiMode($api);
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

        $dc->showAll();

        $this->assertSame($expected, new \ReflectionProperty($dc, 'orderBy')->getValue($dc));
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

        $dc->showAll();

        $this->assertSame(['title DESC', 'alias', 'id'], new \ReflectionProperty($dc, 'orderBy')->getValue($dc));
    }

    public function testApiPaginationDoesNotReadTheBackendSession(): void
    {
        $dc = $this->createSortingDataContainer(null);
        $method = new \ReflectionMethod($dc, 'paginationMenu');

        $this->assertSame('', $method->invoke($dc));
    }

    private function createSortingDataContainer(string|null $sort): DC_Table
    {
        $request = new Request();

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
                $this->setApiMode();
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
                return '';
            }

            protected function parentView(): string
            {
                return '';
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
