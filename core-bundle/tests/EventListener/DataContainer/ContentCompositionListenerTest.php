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
use Contao\CoreBundle\ContentRouting\PageProviderInterface;
use Contao\CoreBundle\EventListener\DataContainer\ContentCompositionListener;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\FrontendUser;
use Contao\Image;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\User;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContentCompositionListenerTest extends TestCase
{
    private $pageRecord = [
        'id' => 17,
        'alias' => 'foo/bar',
        'type' => 'foo',
        'title' => 'foo',
        'published' => '1',
    ];

    private $articleRecord = [
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
     * @var Image&Adapter&MockObject
     */
    private $imageAdapter;

    /**
     * @var Backend&Adapter&MockObject
     */
    private $backendAdapter;

    /**
     * @var PageModel&Adapter&MockObject
     */
    private $pageModelAdapter;

    /**
     * @var ContaoFramework&MockObject
     */
    private $framework;

    /**
     * @var ServiceLocator&MockObject
     */
    private $providers;

    /**
     * @var Connection&MockObject
     */
    private $connection;

    /**
     * @var RequestStack&MockObject
     */
    private $requestStack;

    /**
     * @var ContentCompositionListener
     */
    private $listener;

    protected function setUp(): void
    {
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

        $this->providers = $this->createMock(ServiceLocator::class);

        $this->connection = $this->createMock(Connection::class);

        $this->requestStack = $this->createMock(RequestStack::class);

        $this->listener = new ContentCompositionListener(
            $this->framework,
            $this->security,
            $this->providers,
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

        $page = $this->expectPageWithRow($this->pageRecord);
        $this->mockPageProvider(true, false, $page);

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

    public function testRendersEmptyArticlesOperationIfPageLayoutIsNotFoundWithoutProvider(): void
    {
        $this->security
            ->expects($this->once())
            ->method('isGranted')
            ->with('contao_user.modules', 'article')
            ->willReturn(true)
        ;

        $this->expectPageWithRow($this->pageRecord, null);
        $this->mockPageProvider(false);

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

        $page = $this->expectPageWithRow($this->pageRecord, null);
        $this->mockPageProvider(true, true, $page);

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

        $page = $this->expectPageWithRow($this->pageRecord, 17);
        $this->mockPageProvider(true, true, $page);

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

    public function testRendersArticlesOperationIfProviderSupportsCompositionAndPageLayoutHasArticles(): void
    {
        $this->security
            ->expects($this->once())
            ->method('isGranted')
            ->with('contao_user.modules', 'article')
            ->willReturn(true)
        ;

        $page = $this->expectPageWithRow($this->pageRecord, 0);
        $this->mockPageProvider(true, true, $page);

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

    public function testDoesNotGenerateArticleWithoutBackendUser(): void
    {
        $this->expectRequest(true);
        $this->expectUser(FrontendUser::class);

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

        $this->expectPageWithRow($this->pageRecord);

        $this->providers
            ->expects($this->never())
            ->method('has')
        ;

        /** @var DataContainer&MockObject $dc */
        $dc = $this->mockClassWithProperties(DC_Table::class, ['activeRecord' => (object) $this->pageRecord]);

        $this->listener->generateArticleForPage($dc);
    }

    public function testDoesNotGenerateArticleIfProviderDoesNotSupportContentComposition(): void
    {
        $this->expectRequest(true);
        $this->expectUser();

        $page = $this->expectPageWithRow($this->pageRecord);
        $this->mockPageProvider(true, false, $page);

        /** @var DataContainer&MockObject $dc */
        $dc = $this->mockClassWithProperties(DC_Table::class, ['activeRecord' => (object) $this->pageRecord]);

        $this->listener->generateArticleForPage($dc);
    }

    public function testDoesNotGenerateArticleIfLayoutDoesNotHaveArticles(): void
    {
        $this->expectRequest(true);
        $this->expectUser();

        $page = $this->expectPageWithRow($this->pageRecord, 17);
        $this->mockPageProvider(true, true, $page);

        /** @var DataContainer&MockObject $dc */
        $dc = $this->mockClassWithProperties(DC_Table::class, ['activeRecord' => (object) $this->pageRecord]);

        $this->listener->generateArticleForPage($dc);
    }

    public function testDoesNotGenerateArticleWithoutNewRecords(): void
    {
        $this->expectRequest(true, []);
        $this->expectUser();

        $page = $this->expectPageWithRow($this->pageRecord, 0);
        $this->mockPageProvider(true, true, $page);

        /** @var DataContainer&MockObject $dc */
        $dc = $this->mockClassWithProperties(DC_Table::class, ['activeRecord' => (object) $this->pageRecord]);

        $this->listener->generateArticleForPage($dc);
    }

    public function testDoesNotGenerateArticleIfCurrentPageIsNotInNewRecords(): void
    {
        $this->expectRequest(true, [12]);
        $this->expectUser();

        $page = $this->expectPageWithRow($this->pageRecord, 0);
        $this->mockPageProvider(true, true, $page);

        /** @var DataContainer&MockObject $dc */
        $dc = $this->mockClassWithProperties(DC_Table::class, ['activeRecord' => (object) $this->pageRecord]);

        $this->listener->generateArticleForPage($dc);
    }

    public function testDoesNotGenerateArticleIfPageAlreadyHasArticle(): void
    {
        $this->expectRequest(true, ['tl_foo' => [17]]);
        $this->expectUser();

        $page = $this->expectPageWithRow($this->pageRecord, 0);
        $this->mockPageProvider(true, true, $page);
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
        $page = $this->expectPageWithRow($this->pageRecord, 0);
        $this->mockPageProvider(true, true, $page);
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
        $page = $this->expectPageWithRow($this->pageRecord);
        $this->mockPageProvider(true, true, $page);
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

    public function testCannotPasteArticleWithoutBackendUser(): void
    {
        $this->expectUser(FrontendUser::class);

        /** @var DataContainer&MockObject $dc */
        $dc = $this->mockClassWithProperties(DC_Table::class, ['id' => 17, 'table' => 'tl_article', 'activeRecord' => (object) $this->pageRecord]);

        $this->imageAdapter
            ->expects($this->never())
            ->method('getHtml')
        ;

        $this->assertSame(
            '',
            $this->listener->renderArticlePasteButton($dc, $this->pageRecord, 'tl_page', false)
        );
    }

    public function testCannotPasteIntoArticleIfProviderDoesNotSupportContentComposition(): void
    {
        $this->expectUser();
        $page = $this->expectPageWithRow($this->pageRecord);
        $this->mockPageProvider(true, false, $page);

        /** @var DataContainer&MockObject $dc */
        $dc = $this->mockClassWithProperties(DC_Table::class, ['id' => 17, 'table' => 'tl_article', 'activeRecord' => (object) $this->pageRecord]);

        $this->imageAdapter
            ->expects($this->never())
            ->method('getHtml')
        ;

        $this->assertSame(
            '',
            $this->listener->renderArticlePasteButton($dc, $this->pageRecord, 'tl_page', false)
        );
    }

    public function testCannotPasteIntoArticleIfPageLayoutDoesNotHaveArticles(): void
    {
        $this->expectUser();
        $page = $this->expectPageWithRow($this->pageRecord, 1);
        $this->mockPageProvider(true, true, $page);

        /** @var DataContainer&MockObject $dc */
        $dc = $this->mockClassWithProperties(DC_Table::class, ['id' => 17, 'table' => 'tl_article', 'activeRecord' => (object) $this->pageRecord]);

        $this->imageAdapter
            ->expects($this->never())
            ->method('getHtml')
        ;

        $this->assertSame(
            '',
            $this->listener->renderArticlePasteButton($dc, $this->pageRecord, 'tl_page', false)
        );
    }

    public function testDisablesPasteIntoArticleOnCircularReference(): void
    {
        $this->expectUser();
        $page = $this->expectPageWithRow($this->pageRecord, 0);
        $this->mockPageProvider(true, true, $page);

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
            $this->listener->renderArticlePasteButton($dc, $this->pageRecord, 'tl_page', true)
        );
    }

    public function testDisablesPasteIntoArticleIfUserDoesNotHavePermission(): void
    {
        $user = $this->expectUser();
        $page = $this->expectPageWithRow($this->pageRecord, 0);
        $this->mockPageProvider(true, true, $page);

        $user
            ->expects($this->once())
            ->method('isAllowed')
            ->with(BackendUser::CAN_EDIT_ARTICLE_HIERARCHY, $this->pageRecord)
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
        $user = $this->expectUser();
        $page = $this->expectPageWithRow($this->pageRecord, 0);
        $this->mockPageProvider(true, true, $page);

        $user
            ->expects($this->once())
            ->method('isAllowed')
            ->with(BackendUser::CAN_EDIT_ARTICLE_HIERARCHY, $this->pageRecord)
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
        $this->expectUser();

        $this->pageModelAdapter
            ->expects($this->once())
            ->method('findByPk')
            ->willReturn(null)
        ;

        $this->providers
            ->expects($this->never())
            ->method('has')
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
        $this->expectUser();

        $page = $this->expectPageFindByPk(17, $this->pageRecord);
        $this->mockPageProvider(true, false, $page);

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

    public function testCannotPasteAfterArticleIfPageLayoutDoesNotHaveArticles(): void
    {
        $this->expectUser();

        $page = $this->expectPageFindByPk(17, $this->pageRecord, 17);
        $this->mockPageProvider(true, true, $page);

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

    public function testDisablesPasteAfterArticleOnCutCurrentRecord(): void
    {
        $this->expectUser();

        $page = $this->expectPageFindByPk(17, $this->pageRecord, 0);
        $this->mockPageProvider(true, true, $page);

        /** @var DataContainer&MockObject $dc */
        $dc = $this->mockClassWithProperties(DC_Table::class, ['id' => 17, 'table' => 'tl_article', 'activeRecord' => (object) $this->articleRecord]);

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
        $this->expectUser();

        $page = $this->expectPageFindByPk(17, $this->pageRecord, 0);
        $this->mockPageProvider(true, true, $page);

        /** @var DataContainer&MockObject $dc */
        $dc = $this->mockClassWithProperties(DC_Table::class, ['id' => 17, 'table' => 'tl_article', 'activeRecord' => (object) $this->articleRecord]);

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
        $this->expectUser();

        $page = $this->expectPageFindByPk(17, $this->pageRecord, 0);
        $this->mockPageProvider(true, true, $page);

        /** @var DataContainer&MockObject $dc */
        $dc = $this->mockClassWithProperties(DC_Table::class, ['id' => 17, 'table' => 'tl_article', 'activeRecord' => (object) $this->articleRecord]);

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

    public function testDisablesPasteAfterArticleIfUserDoesNotHavePermission(): void
    {
        $user = $this->expectUser();
        $user
            ->expects($this->once())
            ->method('isAllowed')
            ->with(BackendUser::CAN_EDIT_ARTICLE_HIERARCHY, $this->pageRecord)
            ->willReturn(false)
        ;

        $page = $this->expectPageFindByPk(17, $this->pageRecord, 0);
        $this->mockPageProvider(true, true, $page);

        /** @var DataContainer&MockObject $dc */
        $dc = $this->mockClassWithProperties(DC_Table::class, ['id' => 17, 'table' => 'tl_article', 'activeRecord' => (object) $this->articleRecord]);

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
        $user = $this->expectUser();
        $user
            ->expects($this->once())
            ->method('isAllowed')
            ->with(BackendUser::CAN_EDIT_ARTICLE_HIERARCHY, $this->pageRecord)
            ->willReturn(true)
        ;

        $page = $this->expectPageFindByPk(17, $this->pageRecord, 0);
        $this->mockPageProvider(true, true, $page);

        /** @var DataContainer&MockObject $dc */
        $dc = $this->mockClassWithProperties(DC_Table::class, ['id' => 17, 'table' => 'tl_article', 'activeRecord' => (object) $this->articleRecord]);

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

    /**
     * @return User&MockObject
     */
    private function expectUser(string $userClass = BackendUser::class): User
    {
        /** @var User&MockObject $user */
        $user = $this->mockClassWithProperties($userClass, ['id' => 1]);

        $this->security
            ->expects($this->atLeastOnce())
            ->method('getUser')
            ->willReturn($user)
        ;

        return $user;
    }

    /**
     * @return Request&MockObject
     */
    private function expectRequest(bool $hasSession, array $newRecords = null): Request
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

        return $request;
    }

    /**
     * @param int|false|null $moduleId
     *
     * @return PageModel&MockObject
     */
    private function expectPageWithRow(array $row, $moduleId = false): PageModel
    {
        $page = $this->mockClassWithProperties(PageModel::class, $row);

        $page
            ->expects($this->once())
            ->method('preventSaving')
            ->with(false)
        ;

        $page
            ->expects($this->once())
            ->method('setRow')
            ->with($row)
        ;

        if (false !== $moduleId) {
            if (null !== $moduleId) {
                $moduleId = $this->mockClassWithProperties(
                    LayoutModel::class, [
                        'modules' => serialize([
                            ['mod' => $moduleId, 'col' => 'main'],
                        ]),
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
    private function expectPageFindByPk(int $id, array $row, $moduleId = false): PageModel
    {
        $page = $this->mockClassWithProperties(PageModel::class, $row);

        $page
            ->method('row')
            ->willReturn($row)
        ;

        if (false !== $moduleId) {
            if (null !== $moduleId) {
                $moduleId = $this->mockClassWithProperties(
                    LayoutModel::class, [
                        'modules' => serialize([
                            ['mod' => $moduleId, 'col' => 'main'],
                        ]),
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
            ->with($id)
            ->willReturn($page)
        ;

        return $page;
    }

    private function mockPageProvider(bool $hasProvider, bool $supportComposition = false, PageModel $page = null): void
    {
        $provider = $this->createMock(PageProviderInterface::class);

        $provider
            ->expects($hasProvider ? $this->once() : $this->never())
            ->method('supportsContentComposition')
            ->with($page)
            ->willReturn($supportComposition)
        ;

        $this->providers
            ->expects($this->once())
            ->method('has')
            ->with('foo')
            ->willReturn($hasProvider)
        ;

        $this->providers
            ->expects($hasProvider ? $this->once() : $this->never())
            ->method('get')
//            ->with('foo')
            ->willReturn($provider)
        ;
    }

    private function expectArticleCount(int $count): void
    {
        $statement = $this->createMock(Statement::class);
        $statement
            ->expects($this->once())
            ->method('fetchColumn')
            ->willReturn($count)
        ;

        $this->connection
            ->expects($this->once())
            ->method('executeQuery')
            ->with('SELECT COUNT(*) AS count FROM tl_article WHERE pid=:pid')
            ->willReturn($statement)
        ;
    }
}
