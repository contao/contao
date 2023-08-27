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

use Contao\Backend;
use Contao\BackendUser;
use Contao\CoreBundle\EventListener\DataContainer\ContentCompositionListener;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DC_Table;
use Contao\FrontendUser;
use Contao\Image;
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
use Symfony\Contracts\Translation\TranslatorInterface;

class ContentCompositionListenerTest extends TestCase
{
    private ContentCompositionListener $listener;

    /**
     * @var Security&MockObject
     */
    private Security $security;

    /**
     * @var Adapter<Image>&MockObject
     */
    private Adapter $imageAdapter;

    /**
     * @var Adapter<Backend>&MockObject
     */
    private Adapter $backendAdapter;

    /**
     * @var Adapter<PageModel>&MockObject
     */
    private Adapter $pageModelAdapter;

    /**
     * @var ContaoFramework&MockObject
     */
    private ContaoFramework $framework;

    /**
     * @var PageRegistry&MockObject
     */
    private PageRegistry $pageRegistry;

    /**
     * @var Connection&MockObject
     */
    private Connection $connection;

    /**
     * @var RequestStack&MockObject
     */
    private RequestStack $requestStack;

    private array $pageRecord = [
        'id' => 17,
        'alias' => 'foo/bar',
        'type' => 'foo',
        'title' => 'foo',
        'published' => 1,
    ];

    private array $articleRecord = [
        'id' => 2,
        'pid' => 17,
        'alias' => 'foo-bar',
        'title' => 'foo',
        'published' => 1,
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['TL_DCA']['tl_article']['config']['ptable'] = 'tl_page';

        $this->security = $this->createMock(Security::class);
        $this->imageAdapter = $this->mockAdapter(['getHtml']);
        $this->backendAdapter = $this->mockAdapter(['addToUrl']);
        $this->pageModelAdapter = $this->mockAdapter(['findByPk']);

        $this->framework = $this->mockContaoFramework([
            Image::class => $this->imageAdapter,
            Backend::class => $this->backendAdapter,
            PageModel::class => $this->pageModelAdapter,
        ]);

        $this->pageRegistry = $this->createMock(PageRegistry::class);
        $this->connection = $this->createMock(Connection::class);
        $this->requestStack = $this->createMock(RequestStack::class);

        $this->listener = new ContentCompositionListener(
            $this->framework,
            $this->security,
            $this->pageRegistry,
            $this->createMock(TranslatorInterface::class),
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

        $this->assertSame('', $this->listener->renderPageArticlesOperation([], '', '', '', ''));
    }

    public function testRendersEmptyArticlesOperationIfPageTypeDoesNotSupportComposition(): void
    {
        $this->security
            ->expects($this->once())
            ->method('isGranted')
            ->with('contao_user.modules', 'article')
            ->willReturn(true)
        ;

        $page = $this->mockPageWithRow();

        $this->expectSupportsContentComposition(false, $page);

        $this->imageAdapter
            ->expects($this->once())
            ->method('getHtml')
            ->with('foo--disabled.svg')
            ->willReturn('<img src="foo--disabled.svg">')
        ;

        $this->backendAdapter
            ->expects($this->never())
            ->method('addToUrl')
        ;

        $this->assertSame(
            '<img src="foo--disabled.svg"> ',
            $this->listener->renderPageArticlesOperation($this->pageRecord, '', '', '', 'foo.svg'),
        );
    }

    public function testRendersEmptyArticlesOperationIfPageLayoutDoesNotHaveArticles(): void
    {
        $this->security
            ->expects($this->once())
            ->method('isGranted')
            ->with('contao_user.modules', 'article')
            ->willReturn(true)
        ;

        $page = $this->mockPageWithRow(17);

        $this->expectSupportsContentComposition(true, $page);

        $this->imageAdapter
            ->expects($this->once())
            ->method('getHtml')
            ->with('foo--disabled.svg')
            ->willReturn('<img src="foo--disabled.svg">')
        ;

        $this->backendAdapter
            ->expects($this->never())
            ->method('addToUrl')
        ;

        $this->assertSame(
            '<img src="foo--disabled.svg"> ',
            $this->listener->renderPageArticlesOperation($this->pageRecord, '', '', '', 'foo.svg'),
        );
    }

    public function testRendersNoArticlesIconIfPageSupportsContentCompositionAndIconIsNull(): void
    {
        $this->security
            ->expects($this->once())
            ->method('isGranted')
            ->with('contao_user.modules', 'article')
            ->willReturn(true)
        ;

        $page = $this->mockPageWithRow(17);

        $this->expectSupportsContentComposition(true, $page);

        $this->imageAdapter
            ->expects($this->never())
            ->method('getHtml')
        ;

        $this->backendAdapter
            ->expects($this->never())
            ->method('addToUrl')
        ;

        $this->assertSame('', $this->listener->renderPageArticlesOperation($this->pageRecord, '', '', '', null));
    }

    public function testRendersNoArticlesIconIfPageSupportsContentCompositionAndIconAndHrefAreNull(): void
    {
        $this->security
            ->expects($this->never())
            ->method('isGranted')
        ;

        $this->imageAdapter
            ->expects($this->never())
            ->method('getHtml')
        ;

        $this->backendAdapter
            ->expects($this->never())
            ->method('addToUrl')
        ;

        $this->framework
            ->expects($this->never())
            ->method('createInstance')
        ;

        $this->assertSame('', $this->listener->renderPageArticlesOperation($this->pageRecord, null, '', '', null));
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

        $this->imageAdapter
            ->expects($this->once())
            ->method('getHtml')
            ->with('foo.svg', 'label')
            ->willReturn('<img src="foo.svg" alt="label">')
        ;

        $this->backendAdapter
            ->expects($this->once())
            ->method('addToUrl')
            ->with('link&amp;pn=17')
            ->willReturn('linkWithPn')
        ;

        $this->assertSame(
            '<a href="linkWithPn" title="title"><img src="foo.svg" alt="label"></a> ',
            $this->listener->renderPageArticlesOperation($this->pageRecord, 'link', 'label', 'title', 'foo.svg'),
        );
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

        $this->imageAdapter
            ->expects($this->once())
            ->method('getHtml')
            ->with('foo.svg', 'label')
            ->willReturn('<img src="foo.svg" alt="label">')
        ;

        $this->backendAdapter
            ->expects($this->once())
            ->method('addToUrl')
            ->with('link&amp;pn=17')
            ->willReturn('linkWithPn')
        ;

        $this->assertSame(
            '<a href="linkWithPn" title="title"><img src="foo.svg" alt="label"></a> ',
            $this->listener->renderPageArticlesOperation($this->pageRecord, 'link', 'label', 'title', 'foo.svg'),
        );
    }

    public function testDoesNotGenerateArticleWithoutCurrentRecord(): void
    {
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

    public function testGenerateArticleForNewPage(): void
    {
        ClockMock::withClockMock(true);

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

    public function testCannotPasteIntoArticleIfProviderDoesNotSupportContentComposition(): void
    {
        $page = $this->mockPageWithRow();

        $this->expectSupportsContentComposition(false, $page);

        $dc = $this->mockClassWithProperties(DC_Table::class, ['id' => 17, 'table' => 'tl_article']);
        $dc
            ->method('getCurrentRecord')
            ->willReturn($this->pageRecord)
        ;

        $this->imageAdapter
            ->expects($this->never())
            ->method('getHtml')
        ;

        $this->security
            ->expects($this->never())
            ->method('isGranted')
        ;

        $this->assertSame('', $this->listener->renderArticlePasteButton($dc, $this->pageRecord, 'tl_page', false));
    }

    public function testCannotPasteIntoArticleIfPageLayoutDoesNotHaveArticles(): void
    {
        $page = $this->mockPageWithRow(1);

        $this->expectSupportsContentComposition(true, $page);

        $dc = $this->mockClassWithProperties(DC_Table::class, ['id' => 17, 'table' => 'tl_article']);
        $dc
            ->method('getCurrentRecord')
            ->willReturn($this->pageRecord)
        ;

        $this->imageAdapter
            ->expects($this->never())
            ->method('getHtml')
        ;

        $this->security
            ->expects($this->never())
            ->method('isGranted')
        ;

        $this->assertSame('', $this->listener->renderArticlePasteButton($dc, $this->pageRecord, 'tl_page', false));
    }

    public function testDisablesPasteIntoArticleOnCircularReference(): void
    {
        $page = $this->mockPageWithRow(0);

        $this->expectSupportsContentComposition(true, $page);

        $this->imageAdapter
            ->expects($this->once())
            ->method('getHtml')
            ->with('pasteinto--disabled.svg')
            ->willReturn('<img src="pasteinto--disabled.svg">')
        ;

        $this->security
            ->expects($this->never())
            ->method('isGranted')
        ;

        $dc = $this->mockClassWithProperties(DC_Table::class, ['id' => 17, 'table' => 'tl_article']);
        $dc
            ->method('getCurrentRecord')
            ->willReturn($this->pageRecord)
        ;

        $this->assertSame(
            '<img src="pasteinto--disabled.svg"> ',
            $this->listener->renderArticlePasteButton($dc, $this->pageRecord, 'tl_page', true),
        );
    }

    public function testDisablesPasteIntoArticleIfUserDoesNotHavePermission(): void
    {
        $page = $this->mockPageWithRow(0);

        $this->expectSupportsContentComposition(true, $page);

        $this->security
            ->expects($this->once())
            ->method('isGranted')
            ->with(ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY, $this->pageRecord)
            ->willReturn(false)
        ;

        $this->imageAdapter
            ->expects($this->once())
            ->method('getHtml')
            ->with('pasteinto--disabled.svg')
            ->willReturn('<img src="pasteinto--disabled.svg">')
        ;

        $dc = $this->mockClassWithProperties(DC_Table::class, ['id' => 17, 'table' => 'tl_article']);
        $dc
            ->method('getCurrentRecord')
            ->willReturn($this->pageRecord)
        ;

        $this->assertSame(
            '<img src="pasteinto--disabled.svg"> ',
            $this->listener->renderArticlePasteButton($dc, $this->pageRecord, 'tl_page', false),
        );
    }

    public function testCanPasteIntoArticle(): void
    {
        $page = $this->mockPageWithRow(0);

        $this->expectSupportsContentComposition(true, $page);

        $this->security
            ->expects($this->once())
            ->method('isGranted')
            ->with(ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY, $this->pageRecord)
            ->willReturn(true)
        ;

        $this->imageAdapter
            ->expects($this->once())
            ->method('getHtml')
            ->with('pasteinto.svg')
            ->willReturn('<img src="pasteinto.svg">')
        ;

        $this->backendAdapter
            ->expects($this->once())
            ->method('addToUrl')
            ->willReturn('link')
        ;

        $dc = $this->mockClassWithProperties(DC_Table::class, ['id' => 17, 'table' => 'tl_article']);
        $dc
            ->method('getCurrentRecord')
            ->willReturn($this->pageRecord)
        ;

        $this->assertSame(
            '<a href="link" title="" onclick="Backend.getScrollOffset()"><img src="pasteinto.svg"></a> ',
            $this->listener->renderArticlePasteButton($dc, $this->pageRecord, 'tl_page', false, ['mode' => 'paste', 'id' => 17]),
        );
    }

    public function testCannotPasteAfterArticleIfPageIsNotFound(): void
    {
        $this->pageModelAdapter
            ->expects($this->once())
            ->method('findByPk')
            ->willReturn(null)
        ;

        $this->pageRegistry
            ->expects($this->never())
            ->method('supportsContentComposition')
        ;

        $this->security
            ->expects($this->never())
            ->method('isGranted')
        ;

        $dc = $this->mockClassWithProperties(DC_Table::class, ['id' => 17, 'table' => 'tl_article']);
        $dc
            ->method('getCurrentRecord')
            ->willReturn($this->articleRecord)
        ;

        $this->imageAdapter
            ->expects($this->never())
            ->method('getHtml')
        ;

        $this->assertSame(
            '',
            $this->listener->renderArticlePasteButton($dc, $this->articleRecord, 'tl_article', false),
        );
    }

    public function testCannotPasteAfterArticleIfProviderDoesNotSupportContentComposition(): void
    {
        $page = $this->MockPageFindByPk();

        $this->expectSupportsContentComposition(false, $page);

        $dc = $this->mockClassWithProperties(DC_Table::class, ['id' => 17, 'table' => 'tl_article']);
        $dc
            ->method('getCurrentRecord')
            ->willReturn($this->articleRecord)
        ;

        $this->security
            ->expects($this->never())
            ->method('isGranted')
        ;

        $this->imageAdapter
            ->expects($this->never())
            ->method('getHtml')
        ;

        $this->assertSame(
            '',
            $this->listener->renderArticlePasteButton($dc, $this->articleRecord, 'tl_article', false),
        );
    }

    public function testCannotPasteAfterArticleIfPageLayoutDoesNotHaveArticles(): void
    {
        $page = $this->MockPageFindByPk(17);

        $this->expectSupportsContentComposition(true, $page);

        $dc = $this->mockClassWithProperties(DC_Table::class, ['id' => 17, 'table' => 'tl_article']);
        $dc
            ->method('getCurrentRecord')
            ->willReturn($this->articleRecord)
        ;

        $this->security
            ->expects($this->never())
            ->method('isGranted')
        ;

        $this->imageAdapter
            ->expects($this->never())
            ->method('getHtml')
        ;

        $this->assertSame(
            '',
            $this->listener->renderArticlePasteButton($dc, $this->articleRecord, 'tl_article', false),
        );
    }

    public function testDisablesPasteAfterArticleOnCutCurrentRecord(): void
    {
        $page = $this->MockPageFindByPk(0);

        $this->expectSupportsContentComposition(true, $page);

        $dc = $this->mockClassWithProperties(DC_Table::class, ['id' => 17, 'table' => 'tl_article']);
        $dc
            ->method('getCurrentRecord')
            ->willReturn($this->articleRecord)
        ;

        $this->security
            ->expects($this->never())
            ->method('isGranted')
        ;

        $this->imageAdapter
            ->expects($this->once())
            ->method('getHtml')
            ->with('pasteafter--disabled.svg')
            ->willReturn('<img src="pasteafter--disabled.svg">')
        ;

        $this->assertSame(
            '<img src="pasteafter--disabled.svg"> ',
            $this->listener->renderArticlePasteButton($dc, $this->articleRecord, 'tl_article', false, ['mode' => 'cut', 'id' => 2]),
        );
    }

    public function testDisablesPasteAfterArticleOnCutAllCurrentRecord(): void
    {
        $page = $this->MockPageFindByPk(0);

        $this->expectSupportsContentComposition(true, $page);

        $dc = $this->mockClassWithProperties(DC_Table::class, ['id' => 17, 'table' => 'tl_article']);
        $dc
            ->method('getCurrentRecord')
            ->willReturn($this->articleRecord)
        ;

        $this->security
            ->expects($this->never())
            ->method('isGranted')
        ;

        $this->imageAdapter
            ->expects($this->once())
            ->method('getHtml')
            ->with('pasteafter--disabled.svg')
            ->willReturn('<img src="pasteafter--disabled.svg">')
        ;

        $this->assertSame(
            '<img src="pasteafter--disabled.svg"> ',
            $this->listener->renderArticlePasteButton($dc, $this->articleRecord, 'tl_article', false, ['mode' => 'cutAll', 'id' => [2]]),
        );
    }

    public function testDisablesPasteAfterArticleOnCircularReference(): void
    {
        $page = $this->MockPageFindByPk(0);

        $this->expectSupportsContentComposition(true, $page);

        $dc = $this->mockClassWithProperties(DC_Table::class, ['id' => 17, 'table' => 'tl_article']);
        $dc
            ->method('getCurrentRecord')
            ->willReturn($this->articleRecord)
        ;

        $this->security
            ->expects($this->never())
            ->method('isGranted')
        ;

        $this->imageAdapter
            ->expects($this->once())
            ->method('getHtml')
            ->with('pasteafter--disabled.svg')
            ->willReturn('<img src="pasteafter--disabled.svg">')
        ;

        $this->assertSame(
            '<img src="pasteafter--disabled.svg"> ',
            $this->listener->renderArticlePasteButton($dc, $this->articleRecord, 'tl_article', true, ['mode' => 'paste', 'id' => 17]),
        );
    }

    public function testDisablesPasteAfterArticleIfUserDoesNotHavePermission(): void
    {
        $page = $this->MockPageFindByPk(0);

        $this->expectSupportsContentComposition(true, $page);

        $dc = $this->mockClassWithProperties(DC_Table::class, ['id' => 17, 'table' => 'tl_article']);
        $dc
            ->method('getCurrentRecord')
            ->willReturn($this->articleRecord)
        ;

        $this->security
            ->expects($this->once())
            ->method('isGranted')
            ->with(ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY, $page)
            ->willReturn(false)
        ;

        $this->imageAdapter
            ->expects($this->once())
            ->method('getHtml')
            ->with('pasteafter--disabled.svg')
            ->willReturn('<img src="pasteafter--disabled.svg">')
        ;

        $this->assertSame(
            '<img src="pasteafter--disabled.svg"> ',
            $this->listener->renderArticlePasteButton($dc, $this->articleRecord, 'tl_article', false, ['mode' => 'paste', 'id' => 17]),
        );
    }

    public function testCanPasteAfterArticle(): void
    {
        $pageModel = $this->MockPageFindByPk(0);

        $this->expectSupportsContentComposition(true, $pageModel);

        $dc = $this->mockClassWithProperties(DC_Table::class, ['id' => 17, 'table' => 'tl_article']);
        $dc
            ->method('getCurrentRecord')
            ->willReturn($this->articleRecord)
        ;

        $this->security
            ->expects($this->once())
            ->method('isGranted')
            ->with(ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY, $pageModel)
            ->willReturn(true)
        ;

        $this->imageAdapter
            ->expects($this->once())
            ->method('getHtml')
            ->with('pasteafter.svg')
            ->willReturn('<img src="pasteafter.svg">')
        ;

        $this->backendAdapter
            ->expects($this->once())
            ->method('addToUrl')
            ->willReturn('link')
        ;

        $this->assertSame(
            '<a href="link" title="" onclick="Backend.getScrollOffset()"><img src="pasteafter.svg"></a> ',
            $this->listener->renderArticlePasteButton($dc, $this->articleRecord, 'tl_article', false, ['mode' => 'paste', 'id' => 17]),
        );
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

    /**
     * @return PageModel&MockObject
     */
    private function mockPageWithRow(int|false|null $moduleId = false): PageModel
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
                $moduleId = $this->mockClassWithProperties(
                    LayoutModel::class,
                    [
                        'modules' => serialize([['mod' => $moduleId, 'col' => 'main']]),
                    ],
                );
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

    /**
     * @return PageModel&MockObject
     */
    private function MockPageFindByPk(int|false|null $moduleId = false): PageModel
    {
        $page = $this->mockClassWithProperties(PageModel::class, $this->pageRecord);
        $page
            ->method('row')
            ->willReturn($this->pageRecord)
        ;

        if (false !== $moduleId) {
            if (null !== $moduleId) {
                $moduleId = $this->mockClassWithProperties(
                    LayoutModel::class,
                    [
                        'modules' => serialize([['mod' => $moduleId, 'col' => 'main']]),
                    ],
                );
            }

            $page
                ->expects($this->once())
                ->method('getRelated')
                ->with('layout')
                ->willReturn($moduleId)
            ;
        }

        $this->pageModelAdapter
            ->expects($this->once())
            ->method('findByPk')
            ->with(17)
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
