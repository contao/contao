<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener\DataContainer;

use Contao\BackendUser;
use Contao\CoreBundle\DataContainer\DataContainerOperation;
use Contao\CoreBundle\EventListener\DataContainer\ContentCompositionListener;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\FrontendUser;
use Contao\LayoutModel;
use Contao\PageModel;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bridge\PhpUnit\ClockMock;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class ContentCompositionListenerTest extends TestCase
{
    private ContentCompositionListener $listener;

    private Security&MockObject $security;

    private ContaoFramework&MockObject $framework;

    private PageRegistry&MockObject $pageRegistry;

    private Connection&MockObject $connection;

    private RequestStack&MockObject $requestStack;

    /**
     * @var Adapter<PageModel>&MockObject
     */
    private Adapter&MockObject $pageModelAdapter;

    private array $pageRecord = [
        'id' => 17,
        'alias' => 'foo/bar',
        'type' => 'foo',
        'title' => 'foo',
        'published' => 1,
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['TL_DCA']['tl_article']['config']['ptable'] = 'tl_page';

        $this->security = $this->createMock(Security::class);
        $this->pageModelAdapter = $this->mockAdapter(['findByPk']);

        $this->framework = $this->mockContaoFramework([
            PageModel::class => $this->pageModelAdapter,
        ]);

        $this->pageRegistry = $this->createMock(PageRegistry::class);
        $this->connection = $this->createMock(Connection::class);
        $this->requestStack = $this->createMock(RequestStack::class);

        $this->listener = new ContentCompositionListener(
            $this->framework,
            $this->security,
            $this->pageRegistry,
            $this->connection,
            $this->requestStack,
        );
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA']);

        parent::tearDown();
    }

    public function testDoesNotRenderThePageArticlesOperationIfUserDoesNotHaveAccess(): void
    {
        $this->security
            ->expects($this->once())
            ->method('isGranted')
            ->with('contao_user.modules', 'article')
            ->willReturn(false)
        ;

        $operation = $this->createMock(DataContainerOperation::class);
        $operation
            ->expects($this->once())
            ->method('setHtml')
            ->with('')
        ;

        $this->listener->renderPageArticlesOperation($operation);
    }

    public function testRendersDisabledArticlesOperationIfPageTypeDoesNotSupportComposition(): void
    {
        $this->security
            ->expects($this->once())
            ->method('isGranted')
            ->with('contao_user.modules', 'article')
            ->willReturn(true)
        ;

        $page = $this->mockPageWithRow();

        $this->expectSupportsContentComposition(false, $page);

        $operation = $this->createMock(DataContainerOperation::class);
        $operation
            ->expects($this->once())
            ->method('disable')
        ;

        $operation
            ->expects($this->once())
            ->method('getRecord')
            ->willReturn($this->pageRecord)
        ;

        $this->listener->renderPageArticlesOperation($operation);
    }

    public function testRendersDisabledArticlesOperationIfPageLayoutDoesNotHaveArticles(): void
    {
        $this->security
            ->expects($this->once())
            ->method('isGranted')
            ->with('contao_user.modules', 'article')
            ->willReturn(true)
        ;

        $page = $this->mockPageWithRow(17);

        $this->expectSupportsContentComposition(true, $page);

        $operation = $this->createMock(DataContainerOperation::class);
        $operation
            ->expects($this->once())
            ->method('disable')
        ;

        $operation
            ->expects($this->once())
            ->method('getRecord')
            ->willReturn($this->pageRecord)
        ;

        $this->listener->renderPageArticlesOperation($operation);
    }

    public function testRendersArticlesOperationIfProviderSupportsCompositionAndPageLayoutHasArticles(): void
    {
        $this->security
            ->expects($this->once())
            ->method('isGranted')
            ->with('contao_user.modules', 'article')
            ->willReturn(true)
        ;

        $page = $this->mockPageWithRow(0);

        $this->expectSupportsContentComposition(true, $page);

        $operation = new DataContainerOperation(
            'articles',
            ['href' => 'do=article'],
            $this->pageRecord,
            $this->createMock(DataContainer::class),
        );

        $this->listener->renderPageArticlesOperation($operation);

        $this->assertSame('do=article&amp;pn=17', $operation['href']);
        $this->assertNull($operation->getHtml());
    }

    public function testRendersArticlesOperationIfPageLayoutIsNotFound(): void
    {
        $this->security
            ->expects($this->once())
            ->method('isGranted')
            ->with('contao_user.modules', 'article')
            ->willReturn(true)
        ;

        $page = $this->mockPageWithRow(null);

        $this->expectSupportsContentComposition(true, $page);

        $operation = new DataContainerOperation(
            'articles',
            ['href' => 'do=article'],
            $this->pageRecord,
            $this->createMock(DataContainer::class),
        );

        $this->listener->renderPageArticlesOperation($operation);

        $this->assertSame('do=article&amp;pn=17', $operation['href']);
        $this->assertNull($operation->getHtml());
    }

    public function testDoesNotGenerateArticleWithoutCurrentRecord(): void
    {
        $this->security
            ->expects($this->never())
            ->method('isGranted')
        ;

        $this->requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($this->createMock(Request::class))
        ;

        $this->expectUser();

        $this->framework
            ->expects($this->never())
            ->method('createInstance')
        ;

        $dc = $this->createMock(DC_Table::class);
        $dc
            ->method('getCurrentRecord')
            ->willReturn(null)
        ;

        $this->listener->generateArticleForPage($dc);
    }

    public function testDoesNotGenerateArticleWithoutCurrentRequest(): void
    {
        $this->security
            ->expects($this->never())
            ->method('isGranted')
        ;

        $this->requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(null)
        ;

        $this->expectUser();

        $this->framework
            ->expects($this->never())
            ->method('createInstance')
        ;

        $dc = $this->createMock(DC_Table::class);
        $dc
            ->method('getCurrentRecord')
            ->willReturn(['id' => 17])
        ;

        $this->listener->generateArticleForPage($dc);
    }

    public function testDoesNotGenerateArticleWithoutBackendUser(): void
    {
        $this->security
            ->expects($this->never())
            ->method('isGranted')
        ;

        $request = $this->createMock(Request::class);
        $request
            ->expects($this->never())
            ->method('hasSession')
        ;

        $this->requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request)
        ;

        $user = $this->mockClassWithProperties(FrontendUser::class, ['id' => 1]);

        $this->security
            ->expects($this->atLeastOnce())
            ->method('getUser')
            ->willReturn($user)
        ;

        $this->framework
            ->expects($this->never())
            ->method('createInstance')
        ;

        $dc = $this->createMock(DC_Table::class);
        $dc
            ->method('getCurrentRecord')
            ->willReturn(['id' => 17])
        ;

        $this->listener->generateArticleForPage($dc);
    }

    public function testDoesNotGenerateArticleIfRequestDoesNotHaveASession(): void
    {
        $this->security
            ->expects($this->never())
            ->method('isGranted')
        ;

        $this->expectRequest(false);
        $this->expectUser();

        $this->framework
            ->expects($this->never())
            ->method('createInstance')
        ;

        $dc = $this->createMock(DC_Table::class);
        $dc
            ->method('getCurrentRecord')
            ->willReturn(['id' => 17])
        ;

        $this->listener->generateArticleForPage($dc);
    }

    public function testDoesNotGenerateArticleIfPageTitleIsEmpty(): void
    {
        $this->security
            ->expects($this->never())
            ->method('isGranted')
        ;

        $this->pageRecord['title'] = '';

        $this->expectRequest(true);
        $this->expectUser();
        $this->mockPageWithRow();

        $this->pageRegistry
            ->expects($this->never())
            ->method('supportsContentComposition')
        ;

        $dc = $this->createMock(DC_Table::class);
        $dc
            ->method('getCurrentRecord')
            ->willReturn($this->pageRecord)
        ;

        $this->listener->generateArticleForPage($dc);
    }

    public function testDoesNotGenerateArticleIfProviderDoesNotSupportContentComposition(): void
    {
        $this->security
            ->expects($this->never())
            ->method('isGranted')
        ;

        $this->expectRequest(true);
        $this->expectUser();

        $page = $this->mockPageWithRow();

        $this->expectSupportsContentComposition(false, $page);

        $dc = $this->createMock(DC_Table::class);
        $dc
            ->method('getCurrentRecord')
            ->willReturn($this->pageRecord)
        ;

        $this->listener->generateArticleForPage($dc);
    }

    public function testDoesNotGenerateArticleIfLayoutDoesNotHaveArticles(): void
    {
        $this->security
            ->expects($this->never())
            ->method('isGranted')
        ;

        $this->expectRequest(true);
        $this->expectUser();

        $page = $this->mockPageWithRow(17);

        $this->expectSupportsContentComposition(true, $page);

        $dc = $this->createMock(DC_Table::class);
        $dc
            ->method('getCurrentRecord')
            ->willReturn($this->pageRecord)
        ;

        $this->listener->generateArticleForPage($dc);
    }

    public function testDoesNotGenerateArticleWithoutNewRecords(): void
    {
        $this->security
            ->expects($this->never())
            ->method('isGranted')
        ;

        $this->expectRequest(true, []);
        $this->expectUser();

        $page = $this->mockPageWithRow(0);

        $this->expectSupportsContentComposition(true, $page);

        $dc = $this->createMock(DC_Table::class);
        $dc
            ->method('getCurrentRecord')
            ->willReturn($this->pageRecord)
        ;

        $this->listener->generateArticleForPage($dc);
    }

    public function testDoesNotGenerateArticleIfCurrentPageIsNotInNewRecords(): void
    {
        $this->security
            ->expects($this->never())
            ->method('isGranted')
        ;

        $this->expectRequest(true, [12]);
        $this->expectUser();

        $page = $this->mockPageWithRow(0);

        $this->expectSupportsContentComposition(true, $page);

        $dc = $this->createMock(DC_Table::class);
        $dc
            ->method('getCurrentRecord')
            ->willReturn($this->pageRecord)
        ;

        $this->listener->generateArticleForPage($dc);
    }

    public function testDoesNotGenerateArticleIfPageAlreadyHasArticle(): void
    {
        $this->security
            ->expects($this->never())
            ->method('isGranted')
        ;

        $this->expectRequest(true, ['tl_foo' => [17]]);
        $this->expectUser();

        $page = $this->mockPageWithRow(0);

        $this->expectSupportsContentComposition(true, $page);
        $this->expectArticleCount(1);

        $this->connection
            ->expects($this->never())
            ->method('insert')
        ;

        $dc = $this->mockClassWithProperties(DC_Table::class, ['id' => 17, 'table' => 'tl_foo']);
        $dc
            ->method('getCurrentRecord')
            ->willReturn($this->pageRecord)
        ;

        $this->listener->generateArticleForPage($dc);
    }

    public function testDoesNotGenerateArticleIfPermissionIsDenied(): void
    {
        ClockMock::withClockMock(true);

        $this->security
            ->expects($this->once())
            ->method('isGranted')
            ->with(ContaoCorePermissions::DC_PREFIX.'tl_article')
            ->willReturn(false)
        ;

        $this->expectRequest(true, ['tl_foo' => [17]]);
        $this->expectUser();

        $page = $this->mockPageWithRow(0);

        $this->expectSupportsContentComposition(true, $page);
        $this->expectArticleCount(0);

        $this->connection
            ->expects($this->never())
            ->method('insert')
        ;

        $dc = $this->mockClassWithProperties(DC_Table::class, ['id' => 17, 'table' => 'tl_foo']);
        $dc
            ->method('getCurrentRecord')
            ->willReturn($this->pageRecord)
        ;

        $this->listener->generateArticleForPage($dc);

        ClockMock::withClockMock(false);
    }

    public function testGenerateArticleForNewPage(): void
    {
        ClockMock::withClockMock(true);

        $this->security
            ->expects($this->once())
            ->method('isGranted')
            ->with(ContaoCorePermissions::DC_PREFIX.'tl_article')
            ->willReturn(true)
        ;

        $this->expectRequest(true, ['tl_foo' => [17]]);
        $this->expectUser();

        $page = $this->mockPageWithRow(0);

        $this->expectSupportsContentComposition(true, $page);
        $this->expectArticleCount(0);

        $article = [
            'pid' => 17,
            'sorting' => 128,
            'tstamp' => time(),
            'author' => 1,
            'inColumn' => 'main',
            'title' => 'foo',
            'alias' => 'foo-bar', // Expect folder alias conversion
            'published' => 1,
        ];

        $this->connection
            ->expects($this->once())
            ->method('insert')
            ->with('tl_article', $article)
        ;

        $dc = $this->mockClassWithProperties(DC_Table::class, ['id' => 17, 'table' => 'tl_foo']);
        $dc
            ->method('getCurrentRecord')
            ->willReturn($this->pageRecord)
        ;

        $this->listener->generateArticleForPage($dc);

        ClockMock::withClockMock(false);
    }

    /**
     * @dataProvider moduleConfigProvider
     */
    public function testUsesTheLayoutColumnForNewArticle(array $modules, string $expectedColumn): void
    {
        ClockMock::withClockMock(true);

        $this->security
            ->expects($this->once())
            ->method('isGranted')
            ->with(ContaoCorePermissions::DC_PREFIX.'tl_article')
            ->willReturn(true)
        ;

        $this->expectRequest(true, ['tl_foo' => [17]]);
        $this->expectUser();

        $page = $this->mockPageWithRow();

        $this->expectSupportsContentComposition(true, $page);
        $this->expectArticleCount(0);

        $page
            ->expects($this->once())
            ->method('getRelated')
            ->with('layout')
            ->willReturn($this->mockClassWithProperties(LayoutModel::class, ['modules' => serialize($modules)]))
        ;

        $article = [
            'pid' => 17,
            'sorting' => 128,
            'tstamp' => time(),
            'author' => 1,
            'inColumn' => $expectedColumn,
            'title' => 'foo',
            'alias' => 'foo-bar', // Expect folder alias conversion
            'published' => 1,
        ];

        $this->connection
            ->expects($this->once())
            ->method('insert')
            ->with('tl_article', $article)
        ;

        $dc = $this->mockClassWithProperties(DC_Table::class, ['id' => 17, 'table' => 'tl_foo']);
        $dc
            ->method('getCurrentRecord')
            ->willReturn($this->pageRecord)
        ;

        $this->listener->generateArticleForPage($dc);

        ClockMock::withClockMock(false);
    }

    public function moduleConfigProvider(): \Generator
    {
        yield [
            [
                ['mod' => 0, 'col' => 'main'],
            ],
            'main',
        ];

        yield [
            [
                ['mod' => 1, 'col' => 'foo'],
                ['mod' => 0, 'col' => 'main'],
            ],
            'main',
        ];

        yield [
            [
                ['mod' => 1, 'col' => 'main'],
                ['mod' => 0, 'col' => 'foo'],
            ],
            'foo',
        ];

        yield [
            [
                ['mod' => 1, 'col' => 'main'],
                ['mod' => 2, 'col' => 'foo'],
                ['mod' => 0, 'col' => 'bar'],
                ['mod' => 0, 'col' => 'foo'],
            ],
            'bar',
        ];
    }

    private function expectUser(): void
    {
        $user = $this->mockClassWithProperties(BackendUser::class, ['id' => 1]);

        $this->security
            ->expects($this->atLeastOnce())
            ->method('getUser')
            ->willReturn($user)
        ;
    }

    private function expectRequest(bool $hasSession, array|null $newRecords = null): void
    {
        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('hasSession')
            ->willReturn($hasSession)
        ;

        $session = $this->createMock(SessionInterface::class);

        if (null !== $newRecords) {
            $sessionBag = $this->createMock(AttributeBagInterface::class);
            $sessionBag
                ->expects($this->once())
                ->method('get')
                ->with('new_records')
                ->willReturn($newRecords)
            ;

            $session
                ->expects($this->once())
                ->method('getBag')
                ->with('contao_backend')
                ->willReturn($sessionBag)
            ;
        }

        $request
            ->expects(null === $newRecords ? $this->never() : $this->atLeastOnce())
            ->method('getSession')
            ->willReturn($session)
        ;

        $this->requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request)
        ;
    }

    private function mockPageWithRow(int|false|null $moduleId = false): PageModel&MockObject
    {
        $page = $this->mockClassWithProperties(PageModel::class, $this->pageRecord);
        $page
            ->expects($this->once())
            ->method('preventSaving')
            ->with(false)
        ;

        $page
            ->expects($this->once())
            ->method('setRow')
            ->with($this->pageRecord)
        ;

        if (false !== $moduleId) {
            if (null !== $moduleId) {
                $moduleId = $this->mockClassWithProperties(LayoutModel::class, [
                    'modules' => serialize([['mod' => $moduleId, 'col' => 'main']]),
                ]);
            }

            $page
                ->expects($this->once())
                ->method('getRelated')
                ->with('layout')
                ->willReturn($moduleId)
            ;
        }

        $this->framework
            ->expects($this->once())
            ->method('createInstance')
            ->with(PageModel::class)
            ->willReturn($page)
        ;

        return $page;
    }

    private function expectSupportsContentComposition(bool $supportsComposition, PageModel $pageModel): void
    {
        $this->pageRegistry
            ->expects($this->once())
            ->method('supportsContentComposition')
            ->with($pageModel)
            ->willReturn($supportsComposition)
        ;
    }

    private function expectArticleCount(int $count): void
    {
        $this->connection
            ->expects($this->once())
            ->method('fetchOne')
            ->with('SELECT COUNT(*) FROM tl_article WHERE pid=:pid')
            ->willReturn($count)
        ;
    }
}
