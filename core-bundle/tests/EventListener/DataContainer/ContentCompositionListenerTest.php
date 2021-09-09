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
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\FrontendUser;
use Contao\Image;
use Contao\LayoutModel;
use Contao\PageModel;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContentCompositionListenerTest extends TestCase
{
    private ContentCompositionListener $listener;

    private array $pageRecord = [
        'id' => 17,
        'alias' => 'foo/bar',
        'type' => 'foo',
        'title' => 'foo',
        'published' => '1',
    ];

    private array $articleRecord = [
        'id' => 2,
        'pid' => 17,
        'alias' => 'foo-bar',
        'title' => 'foo',
        'published' => '1',
    ];

    /**
     * @var Security&MockObject
     */
    private $security;

    /**
     * @var Image&MockObject
     */
    private $imageAdapter;

    /**
     * @var Backend&MockObject
     */
    private $backendAdapter;

    /**
     * @var PageModel&MockObject
     */
    private $pageModelAdapter;

    /**
     * @var ContaoFramework&MockObject
     */
    private $framework;

    /**
     * @var PageRegistry&MockObject
     */
    private $pageRegistry;

    /**
     * @var Connection&MockObject
     */
    private $connection;

    /**
     * @var RequestStack&MockObject
     */
    private $requestStack;

    protected function setUp(): void
    {
        $GLOBALS['TL_DCA']['tl_article']['config']['ptable'] = 'tl_page';

        $this->security = $this->createMock(Security::class);

        /** @var Image&MockObject $imageAdapter */
        $imageAdapter = $this->mockAdapter(['getHtml']);
        $this->imageAdapter = $imageAdapter;

        /** @var Backend&MockObject $backendAdapter */
        $backendAdapter = $this->mockAdapter(['addToUrl']);
        $this->backendAdapter = $backendAdapter;

        /** @var PageModel&MockObject $pageModelAdapter */
        $pageModelAdapter = $this->mockAdapter(['findByPk']);
        $this->pageModelAdapter = $pageModelAdapter;

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
            $this->requestStack
        );
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

        $page = $this->expectPageWithRow();

        $this->expectSupportsContentComposition(false, $page);

        $this->imageAdapter
            ->expects($this->once())
            ->method('getHtml')
            ->with('foo_.svg')
            ->willReturn('<img src="foo_.svg">')
        ;

        $this->backendAdapter
            ->expects($this->never())
            ->method('addToUrl')
        ;

        $this->assertSame(
            '<img src="foo_.svg"> ',
            $this->listener->renderPageArticlesOperation($this->pageRecord, '', '', '', 'foo.svg')
        );
    }

    public function testRendersEmptyArticlesOperationIfPageLayoutIsNotFound(): void
    {
        $this->security
            ->expects($this->once())
            ->method('isGranted')
            ->with('contao_user.modules', 'article')
            ->willReturn(true)
        ;

        $page = $this->expectPageWithRow(null);

        $this->expectSupportsContentComposition(true, $page);

        $this->imageAdapter
            ->expects($this->once())
            ->method('getHtml')
            ->with('foo_.svg')
            ->willReturn('<img src="foo_.svg">')
        ;

        $this->backendAdapter
            ->expects($this->never())
            ->method('addToUrl')
        ;

        $this->assertSame(
            '<img src="foo_.svg"> ',
            $this->listener->renderPageArticlesOperation($this->pageRecord, '', '', '', 'foo.svg')
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

        $page = $this->expectPageWithRow(17);

        $this->expectSupportsContentComposition(true, $page);

        $this->imageAdapter
            ->expects($this->once())
            ->method('getHtml')
            ->with('foo_.svg')
            ->willReturn('<img src="foo_.svg">')
        ;

        $this->backendAdapter
            ->expects($this->never())
            ->method('addToUrl')
        ;

        $this->assertSame(
            '<img src="foo_.svg"> ',
            $this->listener->renderPageArticlesOperation($this->pageRecord, '', '', '', 'foo.svg')
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

        $page = $this->expectPageWithRow(17);

        $this->expectSupportsContentComposition(true, $page);

        $this->imageAdapter
            ->expects($this->never())
            ->method('getHtml')
        ;

        $this->backendAdapter
            ->expects($this->never())
            ->method('addToUrl')
        ;

        $this->assertSame(
            '',
            $this->listener->renderPageArticlesOperation($this->pageRecord, '', '', '', null)
        );
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

        $this->assertSame(
            '',
            $this->listener->renderPageArticlesOperation($this->pageRecord, null, '', '', null)
        );
    }

    public function testRendersArticlesOperationIfProviderSupportsCompositionAndPageLayoutHasArticles(): void
    {
        $this->security
            ->expects($this->once())
            ->method('isGranted')
            ->with('contao_user.modules', 'article')
            ->willReturn(true)
        ;

        $page = $this->expectPageWithRow(0);

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
            $this->listener->renderPageArticlesOperation($this->pageRecord, 'link', 'label', 'title', 'foo.svg')
        );
    }

    public function testDoesNotGenerateArticleWithoutActiveRecord(): void
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

        /** @var DataContainer&MockObject $dc */
        $dc = $this->mockClassWithProperties(DC_Table::class, ['activeRecord' => null]);

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

        /** @var DataContainer&MockObject $dc */
        $dc = $this->mockClassWithProperties(DC_Table::class, ['activeRecord' => (object) ['id' => 17]]);

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

        /** @var FrontendUser&MockObject $user */
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

        /** @var DataContainer&MockObject $dc */
        $dc = $this->mockClassWithProperties(DC_Table::class, ['activeRecord' => (object) ['id' => 17]]);

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

        /** @var DataContainer&MockObject $dc */
        $dc = $this->mockClassWithProperties(DC_Table::class, ['activeRecord' => (object) ['id' => 17]]);

        $this->listener->generateArticleForPage($dc);
    }

    public function testDoesNotGenerateArticleIfPageTitleIsEmpty(): void
    {
        $this->pageRecord['title'] = '';

        $this->expectRequest(true);
        $this->expectUser();
        $this->expectPageWithRow();

        $this->pageRegistry
            ->expects($this->never())
            ->method('supportsContentComposition')
        ;

        /** @var DataContainer&MockObject $dc */
        $dc = $this->mockClassWithProperties(DC_Table::class, ['activeRecord' => (object) $this->pageRecord]);

        $this->listener->generateArticleForPage($dc);
    }

    public function testDoesNotGenerateArticleIfProviderDoesNotSupportContentComposition(): void
    {
        $this->expectRequest(true);
        $this->expectUser();

        $page = $this->expectPageWithRow();

        $this->expectSupportsContentComposition(false, $page);

        /** @var DataContainer&MockObject $dc */
        $dc = $this->mockClassWithProperties(DC_Table::class, ['activeRecord' => (object) $this->pageRecord]);

        $this->listener->generateArticleForPage($dc);
    }

    public function testDoesNotGenerateArticleIfLayoutDoesNotHaveArticles(): void
    {
        $this->expectRequest(true);
        $this->expectUser();

        $page = $this->expectPageWithRow(17);

        $this->expectSupportsContentComposition(true, $page);

        /** @var DataContainer&MockObject $dc */
        $dc = $this->mockClassWithProperties(DC_Table::class, ['activeRecord' => (object) $this->pageRecord]);

        $this->listener->generateArticleForPage($dc);
    }

    public function testDoesNotGenerateArticleWithoutNewRecords(): void
    {
        $this->expectRequest(true, []);
        $this->expectUser();

        $page = $this->expectPageWithRow(0);

        $this->expectSupportsContentComposition(true, $page);

        /** @var DataContainer&MockObject $dc */
        $dc = $this->mockClassWithProperties(DC_Table::class, ['activeRecord' => (object) $this->pageRecord]);

        $this->listener->generateArticleForPage($dc);
    }

    public function testDoesNotGenerateArticleIfCurrentPageIsNotInNewRecords(): void
    {
        $this->expectRequest(true, [12]);
        $this->expectUser();

        $page = $this->expectPageWithRow(0);

        $this->expectSupportsContentComposition(true, $page);

        /** @var DataContainer&MockObject $dc */
        $dc = $this->mockClassWithProperties(DC_Table::class, ['activeRecord' => (object) $this->pageRecord]);

        $this->listener->generateArticleForPage($dc);
    }

    public function testDoesNotGenerateArticleIfPageAlreadyHasArticle(): void
    {
        $this->expectRequest(true, ['tl_foo' => [17]]);
        $this->expectUser();

        $page = $this->expectPageWithRow(0);

        $this->expectSupportsContentComposition(true, $page);
        $this->expectArticleCount(1);

        $this->connection
            ->expects($this->never())
            ->method('insert')
        ;

        /** @var DataContainer&MockObject $dc */
        $dc = $this->mockClassWithProperties(DC_Table::class, ['id' => 17, 'table' => 'tl_foo', 'activeRecord' => (object) $this->pageRecord]);

        $this->listener->generateArticleForPage($dc);
    }

    public function testGenerateArticleForNewPage(): void
    {
        $this->expectRequest(true, ['tl_foo' => [17]]);
        $this->expectUser();

        $page = $this->expectPageWithRow(0);

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
            'published' => '1',
        ];

        $this->connection
            ->expects($this->once())
            ->method('insert')
            ->with('tl_article', $article)
        ;

        /** @var DataContainer&MockObject $dc */
        $dc = $this->mockClassWithProperties(DC_Table::class, ['id' => 17, 'table' => 'tl_foo', 'activeRecord' => (object) $this->pageRecord]);

        $this->listener->generateArticleForPage($dc);
    }

    /**
     * @dataProvider moduleConfigProvider
     */
    public function testUsesTheLayoutColumnForNewArticle(array $modules, string $expectedColumn): void
    {
        $this->expectRequest(true, ['tl_foo' => [17]]);
        $this->expectUser();

        $page = $this->expectPageWithRow();

        $this->expectSupportsContentComposition(true, $page);
        $this->expectArticleCount(0);

        $page
            ->expects($this->once())
            ->method('getRelated')
            ->with('layout')
            ->willReturn(
                $this->mockClassWithProperties(LayoutModel::class, ['modules' => serialize($modules)])
            )
        ;

        $article = [
            'pid' => 17,
            'sorting' => 128,
            'tstamp' => time(),
            'author' => 1,
            'inColumn' => $expectedColumn,
            'title' => 'foo',
            'alias' => 'foo-bar', // Expect folder alias conversion
            'published' => '1',
        ];

        $this->connection
            ->expects($this->once())
            ->method('insert')
            ->with('tl_article', $article)
        ;

        /** @var DataContainer&MockObject $dc */
        $dc = $this->mockClassWithProperties(DC_Table::class, ['id' => 17, 'table' => 'tl_foo', 'activeRecord' => (object) $this->pageRecord]);

        $this->listener->generateArticleForPage($dc);
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
        $page = $this->expectPageWithRow();

        $this->expectSupportsContentComposition(false, $page);

        /** @var DataContainer&MockObject $dc */
        $dc = $this->mockClassWithProperties(DC_Table::class, ['id' => 17, 'table' => 'tl_article', 'activeRecord' => (object) $this->pageRecord]);

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
        $page = $this->expectPageWithRow(1);

        $this->expectSupportsContentComposition(true, $page);

        /** @var DataContainer&MockObject $dc */
        $dc = $this->mockClassWithProperties(DC_Table::class, ['id' => 17, 'table' => 'tl_article', 'activeRecord' => (object) $this->pageRecord]);

        $this->imageAdapter
            ->expects($this->never())
            ->method('getHtml')
        ;

        $this->security
            ->expects($this->never())
            ->method('isGranted')
        ;

        $this->assertSame(
            '',
            $this->listener->renderArticlePasteButton($dc, $this->pageRecord, 'tl_page', false)
        );
    }

    public function testDisablesPasteIntoArticleOnCircularReference(): void
    {
        $page = $this->expectPageWithRow(0);

        $this->expectSupportsContentComposition(true, $page);

        $this->imageAdapter
            ->expects($this->once())
            ->method('getHtml')
            ->with('pasteinto_.svg')
            ->willReturn('<img src="pasteinto_.svg">')
        ;

        $this->security
            ->expects($this->never())
            ->method('isGranted')
        ;

        /** @var DataContainer&MockObject $dc */
        $dc = $this->mockClassWithProperties(DC_Table::class, ['id' => 17, 'table' => 'tl_article', 'activeRecord' => (object) $this->pageRecord]);

        $this->assertSame(
            '<img src="pasteinto_.svg"> ',
            $this->listener->renderArticlePasteButton($dc, $this->pageRecord, 'tl_page', true)
        );
    }

    public function testDisablesPasteIntoArticleIfUserDoesNotHavePermission(): void
    {
        $page = $this->expectPageWithRow(0);

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
            ->with('pasteinto_.svg')
            ->willReturn('<img src="pasteinto_.svg">')
        ;

        /** @var DataContainer&MockObject $dc */
        $dc = $this->mockClassWithProperties(DC_Table::class, ['id' => 17, 'table' => 'tl_article', 'activeRecord' => (object) $this->pageRecord]);

        $this->assertSame(
            '<img src="pasteinto_.svg"> ',
            $this->listener->renderArticlePasteButton($dc, $this->pageRecord, 'tl_page', false)
        );
    }

    public function testCanPasteIntoArticle(): void
    {
        $page = $this->expectPageWithRow(0);

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

        /** @var DataContainer&MockObject $dc */
        $dc = $this->mockClassWithProperties(DC_Table::class, ['id' => 17, 'table' => 'tl_article', 'activeRecord' => (object) $this->pageRecord]);

        $this->assertSame(
            '<a href="link" title="" onclick="Backend.getScrollOffset()"><img src="pasteinto.svg"></a> ',
            $this->listener->renderArticlePasteButton($dc, $this->pageRecord, 'tl_page', false, ['mode' => 'paste', 'id' => 17])
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

        /** @var DataContainer&MockObject $dc */
        $dc = $this->mockClassWithProperties(DC_Table::class, ['id' => 17, 'table' => 'tl_article', 'activeRecord' => (object) $this->articleRecord]);

        $this->imageAdapter
            ->expects($this->never())
            ->method('getHtml')
        ;

        $this->assertSame(
            '',
            $this->listener->renderArticlePasteButton($dc, $this->articleRecord, 'tl_article', false)
        );
    }

    public function testCannotPasteAfterArticleIfProviderDoesNotSupportContentComposition(): void
    {
        $page = $this->expectPageFindByPk();

        $this->expectSupportsContentComposition(false, $page);

        /** @var DataContainer&MockObject $dc */
        $dc = $this->mockClassWithProperties(DC_Table::class, ['id' => 17, 'table' => 'tl_article', 'activeRecord' => (object) $this->articleRecord]);

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
            $this->listener->renderArticlePasteButton($dc, $this->articleRecord, 'tl_article', false)
        );
    }

    public function testCannotPasteAfterArticleIfPageLayoutDoesNotHaveArticles(): void
    {
        $page = $this->expectPageFindByPk(17);

        $this->expectSupportsContentComposition(true, $page);

        /** @var DataContainer&MockObject $dc */
        $dc = $this->mockClassWithProperties(DC_Table::class, ['id' => 17, 'table' => 'tl_article', 'activeRecord' => (object) $this->articleRecord]);

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
            $this->listener->renderArticlePasteButton($dc, $this->articleRecord, 'tl_article', false)
        );
    }

    public function testDisablesPasteAfterArticleOnCutCurrentRecord(): void
    {
        $page = $this->expectPageFindByPk(0);

        $this->expectSupportsContentComposition(true, $page);

        /** @var DataContainer&MockObject $dc */
        $dc = $this->mockClassWithProperties(DC_Table::class, ['id' => 17, 'table' => 'tl_article', 'activeRecord' => (object) $this->articleRecord]);

        $this->security
            ->expects($this->never())
            ->method('isGranted')
        ;

        $this->imageAdapter
            ->expects($this->once())
            ->method('getHtml')
            ->with('pasteafter_.svg')
            ->willReturn('<img src="pasteafter_.svg">')
        ;

        $this->assertSame(
            '<img src="pasteafter_.svg"> ',
            $this->listener->renderArticlePasteButton($dc, $this->articleRecord, 'tl_article', false, ['mode' => 'cut', 'id' => 2])
        );
    }

    public function testDisablesPasteAfterArticleOnCutAllCurrentRecord(): void
    {
        $page = $this->expectPageFindByPk(0);

        $this->expectSupportsContentComposition(true, $page);

        /** @var DataContainer&MockObject $dc */
        $dc = $this->mockClassWithProperties(DC_Table::class, ['id' => 17, 'table' => 'tl_article', 'activeRecord' => (object) $this->articleRecord]);

        $this->security
            ->expects($this->never())
            ->method('isGranted')
        ;

        $this->imageAdapter
            ->expects($this->once())
            ->method('getHtml')
            ->with('pasteafter_.svg')
            ->willReturn('<img src="pasteafter_.svg">')
        ;

        $this->assertSame(
            '<img src="pasteafter_.svg"> ',
            $this->listener->renderArticlePasteButton($dc, $this->articleRecord, 'tl_article', false, ['mode' => 'cutAll', 'id' => [2]])
        );
    }

    public function testDisablesPasteAfterArticleOnCircularReference(): void
    {
        $page = $this->expectPageFindByPk(0);

        $this->expectSupportsContentComposition(true, $page);

        /** @var DataContainer&MockObject $dc */
        $dc = $this->mockClassWithProperties(DC_Table::class, ['id' => 17, 'table' => 'tl_article', 'activeRecord' => (object) $this->articleRecord]);

        $this->security
            ->expects($this->never())
            ->method('isGranted')
        ;

        $this->imageAdapter
            ->expects($this->once())
            ->method('getHtml')
            ->with('pasteafter_.svg')
            ->willReturn('<img src="pasteafter_.svg">')
        ;

        $this->assertSame(
            '<img src="pasteafter_.svg"> ',
            $this->listener->renderArticlePasteButton($dc, $this->articleRecord, 'tl_article', true, ['mode' => 'paste', 'id' => 17])
        );
    }

    public function testDisablesPasteAfterArticleIfUserDoesNotHavePermission(): void
    {
        $page = $this->expectPageFindByPk(0);

        $this->expectSupportsContentComposition(true, $page);

        /** @var DataContainer&MockObject $dc */
        $dc = $this->mockClassWithProperties(DC_Table::class, ['id' => 17, 'table' => 'tl_article', 'activeRecord' => (object) $this->articleRecord]);

        $this->security
            ->expects($this->once())
            ->method('isGranted')
            ->with(ContaoCorePermissions::USER_CAN_EDIT_ARTICLE_HIERARCHY, $page)
            ->willReturn(false)
        ;

        $this->imageAdapter
            ->expects($this->once())
            ->method('getHtml')
            ->with('pasteafter_.svg')
            ->willReturn('<img src="pasteafter_.svg">')
        ;

        $this->assertSame(
            '<img src="pasteafter_.svg"> ',
            $this->listener->renderArticlePasteButton($dc, $this->articleRecord, 'tl_article', false, ['mode' => 'paste', 'id' => 17])
        );
    }

    public function testCanPasteAfterArticle(): void
    {
        $pageModel = $this->expectPageFindByPk(0);

        $this->expectSupportsContentComposition(true, $pageModel);

        /** @var DataContainer&MockObject $dc */
        $dc = $this->mockClassWithProperties(DC_Table::class, ['id' => 17, 'table' => 'tl_article', 'activeRecord' => (object) $this->articleRecord]);

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
            $this->listener->renderArticlePasteButton($dc, $this->articleRecord, 'tl_article', false, ['mode' => 'paste', 'id' => 17])
        );
    }

    private function expectUser(): void
    {
        /** @var BackendUser&MockObject $user */
        $user = $this->mockClassWithProperties(BackendUser::class, ['id' => 1]);

        $this->security
            ->expects($this->atLeastOnce())
            ->method('getUser')
            ->willReturn($user)
        ;
    }

    private function expectRequest(bool $hasSession, array $newRecords = null): void
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
     * @param int|false|null $moduleId
     *
     * @return PageModel&MockObject
     */
    private function expectPageWithRow($moduleId = false): PageModel
    {
        /** @var PageModel&MockObject $page */
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
                    ]
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
     * @param int|false|null $moduleId
     *
     * @return PageModel&MockObject
     */
    private function expectPageFindByPk($moduleId = false): PageModel
    {
        /** @var PageModel&MockObject $page */
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
                    ]
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
