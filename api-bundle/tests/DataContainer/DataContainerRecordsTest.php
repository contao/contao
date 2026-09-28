<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Tests\DataContainer;

use Contao\ApiBundle\DataContainer\DataContainerRecordMapper;
use Contao\ApiBundle\DataContainer\DataContainerRecords;
use Contao\ApiBundle\Dto\DataContainerMove;
use Contao\ApiBundle\Dto\DataContainerRecord;
use Contao\ApiBundle\Schema\DataContainerSchemaFactory;
use Contao\ApiBundle\Widget\WidgetConverterRegistry;
use Contao\Controller;
use Contao\CoreBundle\Api\Widget\CoreWidgetConverter;
use Contao\CoreBundle\DataContainer\DcaRequestSwitcher;
use Contao\CoreBundle\DataContainer\DcaUrlAnalyzer;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Widget\DateValueFormatter;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\DcaLoader;
use Contao\TestCase\ContaoTestCase;
use Contao\TextField;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class DataContainerRecordsTest extends ContaoTestCase
{
    private RequestStack $requestStack;

    private array|null $widgets = null;

    private WidgetConverterRegistry $converters;

    protected function setUp(): void
    {
        parent::setUp();

        $this->converters = new WidgetConverterRegistry([new CoreWidgetConverter(new DateValueFormatter($this->createStub(ContaoFramework::class)))]);
        $this->widgets = $GLOBALS['BE_FFL'] ?? null;

        $GLOBALS['BE_FFL']['text'] = TextField::class;
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA'], $GLOBALS['BE_FFL']);

        if (null !== $this->widgets) {
            $GLOBALS['BE_FFL'] = $this->widgets;
        }

        parent::tearDown();
    }

    public function testReadsTheRecordThroughTheDataContainer(): void
    {
        $dc = $this->createMock(DC_Table::class);
        $dc
            ->expects($this->once())
            ->method('getCurrentRecord')
            ->willReturn(['id' => 17, 'title' => 'Example'])
        ;

        $record = $this->createRecords($dc)->find('tl_content', 17);

        $this->assertSame(17, $record->id);
        $this->assertSame(['title' => 'Example'], $record->data);
    }

    #[DataProvider('providePreviousTitles')]
    public function testUpdatesThroughTheBackendEditor(string $previous): void
    {
        $dc = $this->createEditingDataContainer();
        $dc
            ->expects($this->exactly(2))
            ->method('getCurrentRecord')
            ->willReturnOnConsecutiveCalls(['id' => 17, 'title' => $previous], ['id' => 17, 'title' => 'After'])
        ;

        $dc
            ->expects($this->once())
            ->method('edit')
            ->willReturnCallback(
                function (): void {
                    $this->assertSame(['FORM_SUBMIT' => 'tl_content', 'title' => 'After'], $this->requestStack->getCurrentRequest()->request->all());

                    throw new ResponseException(new RedirectResponse('/contao'));
                },
            )
        ;

        $result = $this->createRecords($dc)->update(new DataContainerRecord('tl_content', ['title' => 'After'], 17));

        $this->assertSame(['title' => 'After'], $result->data);
    }

    public static function providePreviousTitles(): iterable
    {
        yield 'changed value' => ['Before'];
        yield 'unchanged value still reaches validation' => ['After'];
    }

    #[DataProvider('provideCreationValues')]
    public function testCreationFollowsTheRedirectIntoAnEditSubmission(array $input, string $default): void
    {
        $dc = $this->createEditingDataContainer();
        $dc
            ->expects($this->once())
            ->method('create')
            ->willThrowException(new ResponseException(new RedirectResponse('/contao?act=edit&id=47')))
        ;

        $dc
            ->expects($this->once())
            ->method('edit')
            ->willReturnCallback(
                function () use ($input, $default): void {
                    $this->assertSame(['FORM_SUBMIT' => 'tl_content', 'title' => $input['title'] ?? $default], $this->requestStack->getCurrentRequest()->request->all());

                    throw new ResponseException(new RedirectResponse('/contao'));
                },
            )
        ;

        $dc
            ->expects($this->exactly(2))
            ->method('getCurrentRecord')
            ->willReturnOnConsecutiveCalls(['id' => 47, 'title' => $default], ['id' => 47, 'title' => $input['title'] ?? $default])
        ;

        $records = $this->createRecords($dc);

        $GLOBALS['TL_DCA']['tl_content']['fields']['title']['eval']['mandatory'] = true;
        $GLOBALS['TL_DCA']['tl_content']['fields']['otherPalette'] = ['inputType' => 'text', 'eval' => ['mandatory' => true]];

        $result = $records->create(new DataContainerRecord('tl_content', $input));

        $this->assertSame(47, $result->id);
        $this->assertSame(['title' => $input['title'] ?? $default], $result->data);
    }

    public static function provideCreationValues(): iterable
    {
        yield 'new value' => [['title' => 'New'], ''];
        yield 'explicit default' => [['title' => 'Default'], 'Default'];
        yield 'explicit empty value' => [['title' => ''], ''];
        yield 'omitted mandatory default' => [[], 'Default'];
        yield 'omitted mandatory empty value' => [[], ''];
    }

    #[DataProvider('provideDefaultSubmissions')]
    public function testSubmitsDefaultsAccordingToThePaletteAndRecordState(int $tstamp, bool $mandatory, bool $changed): void
    {
        $dc = $this->createEditingDataContainer(fn (): string => $changed && !$this->requestStack->getCurrentRequest()->request->has('FORM_SUBMIT') ? 'title' : 'title,alias');
        $dc
            ->method('getCurrentRecord')
            ->willReturn(['id' => 17, 'tstamp' => $tstamp, 'title' => 'Before', 'alias' => ''])
        ;

        $dc
            ->expects($this->once())
            ->method('edit')
            ->willReturnCallback(
                function () use ($tstamp, $mandatory, $changed): void {
                    $expected = ['FORM_SUBMIT' => 'tl_content', 'title' => 'After'];

                    if (0 === $tstamp || ($mandatory && $changed)) {
                        $expected['alias'] = '';
                    }

                    $this->assertSame($expected, $this->requestStack->getCurrentRequest()->request->all());

                    throw new ResponseException(new RedirectResponse('/contao'));
                },
            )
        ;

        $records = $this->createRecords($dc);

        $GLOBALS['TL_DCA']['tl_content']['fields']['alias'] = ['inputType' => 'text', 'sql' => ['type' => 'string'], 'eval' => ['mandatory' => $mandatory]];

        $records->update(new DataContainerRecord('tl_content', ['title' => 'After'], 17));
    }

    public static function provideDefaultSubmissions(): iterable
    {
        yield 'creation includes optional defaults' => [0, false, false];
        yield 'new mandatory field on update' => [123, true, true];
        yield 'unchanged mandatory field stays omitted' => [123, true, false];
        yield 'new optional field stays omitted' => [123, false, true];
        yield 'unchanged optional field stays omitted' => [123, false, false];
    }

    public function testDeletesWithAnIsolatedBackendSession(): void
    {
        $dc = $this->createMock(DC_Table::class);
        $dc
            ->expects($this->once())
            ->method('getCurrentRecord')
            ->willReturn(['id' => 17])
        ;

        $dc
            ->expects($this->once())
            ->method('delete')
            ->with(true)
            ->willReturnCallback(
                function (): void {
                    $request = $this->requestStack->getCurrentRequest();
                    $this->assertNotSame($this->requestStack->getMainRequest()->getSession(), $request->getSession());
                    $this->assertNull($request->getSession()->get('marker'));
                    $this->assertInstanceOf(AttributeBagInterface::class, $request->getSession()->getBag('contao_backend'));
                },
            )
        ;

        $records = $this->createRecords($dc);
        $session = new Session(new MockArraySessionStorage());
        $session->set('marker', 'saved backend state');
        $this->requestStack->getCurrentRequest()->setSession($session);

        $records->delete(new DataContainerRecord('tl_content', [], 17));

        $this->assertSame('saved backend state', $session->get('marker'));
    }

    public function testReportsTheMaximumPageForTheRequestedPageSize(): void
    {
        $records = $this->createRecords($this->createStub(DC_Table::class));

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessage(\sprintf('The page must not exceed %d for a page size of 30.', intdiv(PHP_INT_MAX, 30) + 1));

        $records->list('tl_content', PHP_INT_MAX);
    }

    #[DataProvider('provideListingPages')]
    public function testUsesTheDataContainerListingWithoutQueryingTheDatabase(int $size, array $expected): void
    {
        $dc = $this->createMock(DC_Table::class);
        $dc
            ->expects($this->once())
            ->method('showAll')
            ->willReturnCallback(
                function () use ($size) {
                    $this->assertSame(2 * $size, $this->requestStack->getCurrentRequest()->attributes->get('_contao_api_listing_limit'));
                    $this->assertTrue($this->requestStack->getCurrentRequest()->attributes->get('_contao_api'));
                    $this->assertTrue($this->requestStack->getCurrentRequest()->hasSession());
                    $bag = $this->requestStack->getCurrentRequest()->getSession()->getBag('contao_backend');
                    $this->assertInstanceOf(AttributeBagInterface::class, $bag);
                    $this->assertSame(['sorting' => ['tl_content' => 'title DESC']], $bag->all());
                    $this->assertNull($this->requestStack->getCurrentRequest()->query->get('sort'));
                    $this->assertSame([], $this->requestStack->getCurrentRequest()->attributes->get('_contao_api_listing_ids'));
                    $this->requestStack->getCurrentRequest()->attributes->set('_contao_api_listing_ids', range(1, 33));

                    return '';
                },
            )
        ;

        $dc
            ->method('getCurrentRecord')
            ->willReturnCallback(static fn ($id) => ['id' => $id, 'title' => 'Record '.$id])
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->never())
            ->method('executeQuery')
        ;

        $connection
            ->expects($this->never())
            ->method('iterateColumn')
        ;

        $page = $this->createRecords($dc, $connection)->list('tl_content', 2, itemsPerPage: $size, sort: ['title DESC']);

        $this->assertSame(2.0, $page->getCurrentPage());
        $this->assertSame((float) $size, $page->getItemsPerPage());
        $this->assertSame($expected, array_map(static fn ($record) => $record->id, iterator_to_array($page)));
    }

    public function testPassesTheSortingChoiceToTheDataContainer(): void
    {
        $dc = $this->createMock(DC_Table::class);
        $dc
            ->expects($this->once())
            ->method('showAll')
            ->willReturnCallback(
                function (): string {
                    $bag = $this->requestStack->getCurrentRequest()->getSession()->getBag('contao_backend');
                    $this->assertInstanceOf(AttributeBagInterface::class, $bag);
                    $this->assertSame(['tl_content' => 'title'], $bag->get('sorting'));

                    return '';
                },
            )
        ;

        $records = $this->createRecords($dc);
        $records->list('tl_content', sort: ['title']);
    }

    public function testListingDoesNotInheritTheBackendSession(): void
    {
        $dc = $this->createMock(DC_Table::class);
        $dc
            ->expects($this->once())
            ->method('showAll')
            ->willReturnCallback(
                function (): string {
                    $this->assertNull($this->requestStack->getCurrentRequest()->getSession()->get('marker'));

                    return '';
                },
            )
        ;

        $records = $this->createRecords($dc);
        $session = new Session(new MockArraySessionStorage());
        $session->set('marker', 'saved backend state');
        $this->requestStack->getCurrentRequest()->setSession($session);

        $records->list('tl_content');

        $this->assertSame('saved backend state', $session->get('marker'));
    }

    public function testListingLimitDoesNotOverflow(): void
    {
        $dc = $this->createMock(DC_Table::class);
        $dc
            ->expects($this->once())
            ->method('showAll')
            ->willReturnCallback(
                function () {
                    $this->assertSame(PHP_INT_MAX, $this->requestStack->getCurrentRequest()->attributes->get('_contao_api_listing_limit'));

                    return '';
                },
            )
        ;

        $this->assertCount(0, $this->createRecords($dc)->list('tl_content', PHP_INT_MAX, itemsPerPage: 1));
    }

    public static function provideListingPages(): iterable
    {
        yield 'default size' => [30, [31, 32, 33]];
        yield 'custom size' => [10, range(11, 20)];
        yield 'empty page' => [40, []];
    }

    public function testMovesThroughTheExistingCutAction(): void
    {
        $dc = $this->createMock(DC_Table::class);
        $dc
            ->expects($this->exactly(3))
            ->method('getCurrentRecord')
            ->willReturn(['id' => 17, 'title' => 'Moved'])
        ;

        $dc
            ->expects($this->once())
            ->method('cut')
            ->with(true, 42, DataContainer::PASTE_AFTER)
        ;

        $result = $this->createRecords($dc)->move('tl_content', 17, new DataContainerMove(42, 'after'));

        $this->assertSame(17, $result->id);
    }

    public function testRejectsAMissingMoveDestinationBeforeCutting(): void
    {
        $dc = $this->createMock(DC_Table::class);
        $dc
            ->expects($this->exactly(2))
            ->method('getCurrentRecord')
            ->willReturnOnConsecutiveCalls(['id' => 17], null)
        ;

        $dc
            ->expects($this->never())
            ->method('cut')
        ;

        $this->expectException(NotFoundHttpException::class);
        $this->createRecords($dc)->move('tl_content', 17, new DataContainerMove(999, 'after'));
    }

    private function createEditingDataContainer(\Closure|string $palette = '{title_legend},title;'): DC_Table&MockObject
    {
        $dc = $this->createMock(DC_Table::class);
        $dc
            ->method('__get')
            ->willReturnMap([['table', 'tl_content']])
        ;

        $dc
            ->method('getPalette')
            ->willReturnCallback($palette instanceof \Closure ? $palette : static fn () => $palette)
        ;

        return $dc;
    }

    private function createRecords(DC_Table $dc, Connection|null $connection = null): DataContainerRecords
    {
        $GLOBALS['TL_DCA']['tl_content']['fields'] = ['title' => ['inputType' => 'text', 'sql' => ['type' => 'string'], 'sorting' => true, 'flag' => DataContainer::SORT_BOTH]];
        $GLOBALS['TL_DCA']['tl_content']['list']['sorting'] = ['mode' => DataContainer::MODE_SORTABLE, 'panelLayout' => 'sort'];
        $GLOBALS['TL_DCA']['tl_content']['config']['dataContainer'] = DC_Table::class;

        $controller = $this->createAdapterStub(['loadDataContainer']);
        $loader = $this->createAdapterStub(['switchToCurrentRequest']);

        $framework = $this->createContaoFrameworkStub([Controller::class => $controller, DcaLoader::class => $loader]);
        $framework
            ->method('createInstance')
            ->willReturnCallback(
                function ($driver, $arguments) use ($dc) {
                    $this->assertSame(DC_Table::class, $driver);
                    $this->assertSame(['tl_content'], $arguments);
                    $this->assertSame('backend', $this->requestStack->getCurrentRequest()->attributes->get('_scope'));

                    return $dc;
                },
            )
        ;

        $mapper = new DataContainerRecordMapper(new DataContainerSchemaFactory($framework, $this->converters), $this->converters);

        if (!$connection) {
            $connection = $this->createStub(Connection::class);
            $connection
                ->method('transactional')
                ->willReturnCallback(static fn ($callback) => $callback())
            ;
        }

        $stack = $this->requestStack = new RequestStack();
        $stack->push(Request::create('/contao', 'POST'));

        $analyzer = $this->createStub(DcaUrlAnalyzer::class);
        $analyzer
            ->method('getEditUrl')
            ->willReturn('/contao?do=article')
        ;

        $router = $this->createStub(UrlGeneratorInterface::class);
        $router
            ->method('generate')
            ->willReturnCallback(static fn ($route, $parameters) => '/contao?'.http_build_query($parameters))
        ;

        return new DataContainerRecords($mapper, $connection, $framework, $stack, $analyzer, $router, new DcaRequestSwitcher($framework, $stack));
    }
}
