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
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DC_Table;
use Contao\FrontendUser;
use Contao\LayoutModel;
use Contao\PageModel;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ContentCompositionListenerTest extends TestCase
{
    private Security&MockObject $security;

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
            ->method('hide')
        ;

        $listener = $this->getListener();
        $listener->renderPageArticlesOperation($operation);
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

        $framework = $this->createContaoFrameworkMock();
        $framework
            ->expects($this->once())
            ->method('createInstance')
            ->with(PageModel::class)
            ->willReturn($page)
        ;

        $pageRegistry = $this->createMock(PageRegistry::class);
        $pageRegistry
            ->expects($this->once())
            ->method('supportsContentComposition')
            ->with($page)
            ->willReturn(false)
        ;

        $operation = $this->createMock(DataContainerOperation::class);
        $operation
            ->expects($this->once())
            ->method('hide')
        ;

        $operation
            ->expects($this->once())
            ->method('getRecord')
            ->willReturn($this->pageRecord)
        ;

        $listener = $this->getListener($framework, pageRegistry: $pageRegistry);
        $listener->renderPageArticlesOperation($operation);
    }

    public function testRendersDisabledArticlesOperationIfPageLayoutDoesNotHaveArticles(): void
    {
        $this->security
            ->expects($this->once())
            ->method('isGranted')
            ->with('contao_user.modules', 'article')
            ->willReturn(true)
        ;

        $page = $this->mockPageWithRow();

        $layout = $this->createClassWithPropertiesStub(LayoutModel::class, [
            'modules' => serialize([['mod' => 17, 'col' => 'main']]),
        ]);

        $layoutAdapter = $this->createAdapterMock(['findById']);
        $layoutAdapter
            ->expects($this->once())
            ->method('findById')
            ->willReturn($layout)
        ;

        $framework = $this->createContaoFrameworkMock([LayoutModel::class => $layoutAdapter]);
        $framework
            ->expects($this->once())
            ->method('createInstance')
            ->with(PageModel::class)
            ->willReturn($page)
        ;

        $pageRegistry = $this->createMock(PageRegistry::class);
        $pageRegistry
            ->expects($this->once())
            ->method('supportsContentComposition')
            ->with($page)
            ->willReturn(true)
        ;

        $operation = $this->createMock(DataContainerOperation::class);
        $operation
            ->expects($this->once())
            ->method('hide')
        ;

        $operation
            ->expects($this->once())
            ->method('getRecord')
            ->willReturn($this->pageRecord)
        ;

        $listener = $this->getListener($framework, pageRegistry: $pageRegistry);
        $listener->renderPageArticlesOperation($operation);
    }

    public function testRendersArticlesOperationIfProviderSupportsCompositionAndPageLayoutHasArticles(): void
    {
        $this->security
            ->expects($this->once())
            ->method('isGranted')
            ->with('contao_user.modules', 'article')
            ->willReturn(true)
        ;

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with('contao_backend', ['do' => 'article', 'pn' => 17])
            ->willReturn('/contao?do=article&pn=17')
        ;

        $page = $this->mockPageWithRow();

        $layout = $this->createClassWithPropertiesStub(LayoutModel::class, [
            'modules' => serialize([['mod' => 0, 'col' => 'main']]),
        ]);

        $layoutAdapter = $this->createAdapterMock(['findById']);
        $layoutAdapter
            ->expects($this->once())
            ->method('findById')
            ->willReturn($layout)
        ;

        $framework = $this->createContaoFrameworkMock([LayoutModel::class => $layoutAdapter]);
        $framework
            ->expects($this->once())
            ->method('createInstance')
            ->with(PageModel::class)
            ->willReturn($page)
        ;

        $pageRegistry = $this->createMock(PageRegistry::class);
        $pageRegistry
            ->expects($this->once())
            ->method('supportsContentComposition')
            ->with($page)
            ->willReturn(true)
        ;

        $operation = $this->createMock(DataContainerOperation::class);
        $operation
            ->expects($this->once())
            ->method('setUrl')
            ->with('/contao?do=article&pn=17')
        ;

        $operation
            ->expects($this->once())
            ->method('getRecord')
            ->willReturn($this->pageRecord)
        ;

        $listener = $this->getListener($framework, pageRegistry: $pageRegistry, urlGenerator: $urlGenerator);
        $listener->renderPageArticlesOperation($operation);
    }

    public function testRendersArticlesOperationIfPageLayoutIsNotFound(): void
    {
        $this->security
            ->expects($this->once())
            ->method('isGranted')
            ->with('contao_user.modules', 'article')
            ->willReturn(true)
        ;

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with('contao_backend', ['do' => 'article', 'pn' => 17])
            ->willReturn('/contao?do=article&pn=17')
        ;

        $page = $this->mockPageWithRow();

        $layoutAdapter = $this->createAdapterMock(['findById']);
        $layoutAdapter
            ->expects($this->once())
            ->method('findById')
            ->willReturn(null)
        ;

        $framework = $this->createContaoFrameworkMock([LayoutModel::class => $layoutAdapter]);
        $framework
            ->expects($this->once())
            ->method('createInstance')
            ->with(PageModel::class)
            ->willReturn($page)
        ;

        $pageRegistry = $this->createMock(PageRegistry::class);
        $pageRegistry
            ->expects($this->once())
            ->method('supportsContentComposition')
            ->with($page)
            ->willReturn(true)
        ;

        $operation = $this->createMock(DataContainerOperation::class);
        $operation
            ->expects($this->once())
            ->method('setUrl')
            ->with('/contao?do=article&pn=17')
        ;

        $operation
            ->expects($this->once())
            ->method('getRecord')
            ->willReturn($this->pageRecord)
        ;

        $listener = $this->getListener($framework, pageRegistry: $pageRegistry, urlGenerator: $urlGenerator);
        $listener->renderPageArticlesOperation($operation);
    }

    public function testDoesNotGenerateArticleWithoutCurrentRecord(): void
    {
        $this->security
            ->expects($this->never())
            ->method('isGranted')
        ;

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($this->createStub(Request::class))
        ;

        $this->expectUser();

        $framework = $this->createContaoFrameworkMock();
        $framework
            ->expects($this->never())
            ->method('createInstance')
        ;

        $dc = $this->createStub(DC_Table::class);
        $dc
            ->method('getCurrentRecord')
            ->willReturn(null)
        ;

        $listener = $this->getListener($framework, requestStack: $requestStack);
        $listener->generateArticleForPage($dc);
    }

    public function testDoesNotGenerateArticleWithoutCurrentRequest(): void
    {
        $this->security
            ->expects($this->never())
            ->method('isGranted')
        ;

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(null)
        ;

        $this->expectUser();

        $framework = $this->createContaoFrameworkMock();
        $framework
            ->expects($this->never())
            ->method('createInstance')
        ;

        $dc = $this->createStub(DC_Table::class);
        $dc
            ->method('getCurrentRecord')
            ->willReturn(['id' => 17])
        ;

        $listener = $this->getListener($framework, requestStack: $requestStack);
        $listener->generateArticleForPage($dc);
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

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request)
        ;

        $user = $this->createClassWithPropertiesStub(FrontendUser::class, ['id' => 1]);

        $this->security
            ->expects($this->atLeastOnce())
            ->method('getUser')
            ->willReturn($user)
        ;

        $framework = $this->createContaoFrameworkMock();
        $framework
            ->expects($this->never())
            ->method('createInstance')
        ;

        $dc = $this->createStub(DC_Table::class);
        $dc
            ->method('getCurrentRecord')
            ->willReturn(['id' => 17])
        ;

        $listener = $this->getListener($framework, requestStack: $requestStack);
        $listener->generateArticleForPage($dc);
    }

    public function testDoesNotGenerateArticleIfRequestDoesNotHaveASession(): void
    {
        $this->security
            ->expects($this->never())
            ->method('isGranted')
        ;

        $requestStack = $this->createMock(RequestStack::class);

        $this->expectRequest($requestStack, false);
        $this->expectUser();

        $framework = $this->createContaoFrameworkMock();
        $framework
            ->expects($this->never())
            ->method('createInstance')
        ;

        $dc = $this->createStub(DC_Table::class);
        $dc
            ->method('getCurrentRecord')
            ->willReturn(['id' => 17])
        ;

        $listener = $this->getListener($framework, requestStack: $requestStack);
        $listener->generateArticleForPage($dc);
    }

    public function testDoesNotGenerateArticleIfPageTitleIsEmpty(): void
    {
        $this->security
            ->expects($this->never())
            ->method('isGranted')
        ;

        $this->pageRecord['title'] = '';

        $requestStack = $this->createMock(RequestStack::class);

        $this->expectRequest($requestStack, true);
        $this->expectUser();

        $page = $this->mockPageWithRow();

        $framework = $this->createContaoFrameworkMock();
        $framework
            ->expects($this->once())
            ->method('createInstance')
            ->with(PageModel::class)
            ->willReturn($page)
        ;

        $pageRegistry = $this->createMock(PageRegistry::class);
        $pageRegistry
            ->expects($this->never())
            ->method('supportsContentComposition')
        ;

        $dc = $this->createStub(DC_Table::class);
        $dc
            ->method('getCurrentRecord')
            ->willReturn($this->pageRecord)
        ;

        $listener = $this->getListener($framework, pageRegistry: $pageRegistry, requestStack: $requestStack);
        $listener->generateArticleForPage($dc);
    }

    public function testDoesNotGenerateArticleIfProviderDoesNotSupportContentComposition(): void
    {
        $this->security
            ->expects($this->never())
            ->method('isGranted')
        ;

        $requestStack = $this->createMock(RequestStack::class);

        $this->expectRequest($requestStack, true);
        $this->expectUser();

        $page = $this->mockPageWithRow();

        $framework = $this->createContaoFrameworkMock();
        $framework
            ->expects($this->once())
            ->method('createInstance')
            ->with(PageModel::class)
            ->willReturn($page)
        ;

        $pageRegistry = $this->createMock(PageRegistry::class);
        $pageRegistry
            ->expects($this->once())
            ->method('supportsContentComposition')
            ->with($page)
            ->willReturn(false)
        ;

        $dc = $this->createStub(DC_Table::class);
        $dc
            ->method('getCurrentRecord')
            ->willReturn($this->pageRecord)
        ;

        $listener = $this->getListener($framework, pageRegistry: $pageRegistry, requestStack: $requestStack);
        $listener->generateArticleForPage($dc);
    }

    public function testDoesNotGenerateArticleIfLayoutDoesNotHaveArticles(): void
    {
        $this->security
            ->expects($this->never())
            ->method('isGranted')
        ;

        $requestStack = $this->createMock(RequestStack::class);

        $this->expectRequest($requestStack, true);
        $this->expectUser();

        $page = $this->mockPageWithRow();

        $layout = $this->createClassWithPropertiesStub(LayoutModel::class, [
            'modules' => serialize([['mod' => 17, 'col' => 'main']]),
        ]);

        $layoutAdapter = $this->createAdapterMock(['findById']);
        $layoutAdapter
            ->expects($this->once())
            ->method('findById')
            ->willReturn($layout)
        ;

        $framework = $this->createContaoFrameworkMock([LayoutModel::class => $layoutAdapter]);
        $framework
            ->expects($this->once())
            ->method('createInstance')
            ->with(PageModel::class)
            ->willReturn($page)
        ;

        $pageRegistry = $this->createMock(PageRegistry::class);
        $pageRegistry
            ->expects($this->once())
            ->method('supportsContentComposition')
            ->with($page)
            ->willReturn(true)
        ;

        $dc = $this->createStub(DC_Table::class);
        $dc
            ->method('getCurrentRecord')
            ->willReturn($this->pageRecord)
        ;

        $listener = $this->getListener($framework, pageRegistry: $pageRegistry, requestStack: $requestStack);
        $listener->generateArticleForPage($dc);
    }

    public function testDoesNotGenerateArticleWithoutNewRecords(): void
    {
        $this->security
            ->expects($this->never())
            ->method('isGranted')
        ;

        $requestStack = $this->createMock(RequestStack::class);

        $this->expectRequest($requestStack, true, []);
        $this->expectUser();

        $page = $this->mockPageWithRow();

        $layout = $this->createClassWithPropertiesStub(LayoutModel::class, [
            'modules' => serialize([['mod' => 0, 'col' => 'main']]),
        ]);

        $layoutAdapter = $this->createAdapterMock(['findById']);
        $layoutAdapter
            ->expects($this->once())
            ->method('findById')
            ->willReturn($layout)
        ;

        $framework = $this->createContaoFrameworkMock([LayoutModel::class => $layoutAdapter]);
        $framework
            ->expects($this->once())
            ->method('createInstance')
            ->with(PageModel::class)
            ->willReturn($page)
        ;

        $pageRegistry = $this->createMock(PageRegistry::class);
        $pageRegistry
            ->expects($this->once())
            ->method('supportsContentComposition')
            ->with($page)
            ->willReturn(true)
        ;

        $dc = $this->createStub(DC_Table::class);
        $dc
            ->method('getCurrentRecord')
            ->willReturn($this->pageRecord)
        ;

        $listener = $this->getListener($framework, pageRegistry: $pageRegistry, requestStack: $requestStack);
        $listener->generateArticleForPage($dc);
    }

    public function testDoesNotGenerateArticleIfCurrentPageIsNotInNewRecords(): void
    {
        $this->security
            ->expects($this->never())
            ->method('isGranted')
        ;

        $requestStack = $this->createMock(RequestStack::class);

        $this->expectRequest($requestStack, true, [12]);
        $this->expectUser();

        $page = $this->mockPageWithRow();

        $layout = $this->createClassWithPropertiesStub(LayoutModel::class, [
            'modules' => serialize([['mod' => 0, 'col' => 'main']]),
        ]);

        $layoutAdapter = $this->createAdapterMock(['findById']);
        $layoutAdapter
            ->expects($this->once())
            ->method('findById')
            ->willReturn($layout)
        ;

        $framework = $this->createContaoFrameworkMock([LayoutModel::class => $layoutAdapter]);
        $framework
            ->expects($this->once())
            ->method('createInstance')
            ->with(PageModel::class)
            ->willReturn($page)
        ;

        $pageRegistry = $this->createMock(PageRegistry::class);
        $pageRegistry
            ->expects($this->once())
            ->method('supportsContentComposition')
            ->with($page)
            ->willReturn(true)
        ;

        $dc = $this->createClassWithPropertiesStub(DC_Table::class, ['table' => 'tl_page']);
        $dc
            ->method('getCurrentRecord')
            ->willReturn($this->pageRecord)
        ;

        $listener = $this->getListener($framework, pageRegistry: $pageRegistry, requestStack: $requestStack);
        $listener->generateArticleForPage($dc);
    }

    public function testDoesNotGenerateArticleIfPageAlreadyHasArticle(): void
    {
        $this->security
            ->expects($this->never())
            ->method('isGranted')
        ;

        $requestStack = $this->createMock(RequestStack::class);

        $this->expectRequest($requestStack, true, ['tl_foo' => [17]]);
        $this->expectUser();

        $page = $this->mockPageWithRow();

        $layout = $this->createClassWithPropertiesStub(LayoutModel::class, [
            'modules' => serialize([['mod' => 0, 'col' => 'main']]),
        ]);

        $layoutAdapter = $this->createAdapterMock(['findById']);
        $layoutAdapter
            ->expects($this->once())
            ->method('findById')
            ->willReturn($layout)
        ;

        $framework = $this->createContaoFrameworkMock([LayoutModel::class => $layoutAdapter]);
        $framework
            ->expects($this->once())
            ->method('createInstance')
            ->with(PageModel::class)
            ->willReturn($page)
        ;

        $pageRegistry = $this->createMock(PageRegistry::class);
        $pageRegistry
            ->expects($this->once())
            ->method('supportsContentComposition')
            ->with($page)
            ->willReturn(true)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->never())
            ->method('insert')
        ;

        $connection
            ->expects($this->once())
            ->method('fetchOne')
            ->with('SELECT COUNT(*) FROM tl_article WHERE pid = :pid')
            ->willReturn(1)
        ;

        $dc = $this->createClassWithPropertiesStub(DC_Table::class, ['id' => 17, 'table' => 'tl_foo']);
        $dc
            ->method('getCurrentRecord')
            ->willReturn($this->pageRecord)
        ;

        $listener = $this->getListener($framework, pageRegistry: $pageRegistry, connection: $connection, requestStack: $requestStack);
        $listener->generateArticleForPage($dc);
    }

    public function testDoesNotGenerateArticleIfPermissionIsDenied(): void
    {
        $this->security
            ->expects($this->once())
            ->method('isGranted')
            ->with(ContaoCorePermissions::DC_PREFIX.'tl_article')
            ->willReturn(false)
        ;

        $requestStack = $this->createMock(RequestStack::class);

        $this->expectRequest($requestStack, true, ['tl_foo' => [17]]);
        $this->expectUser();

        $page = $this->mockPageWithRow();

        $layout = $this->createClassWithPropertiesStub(LayoutModel::class, [
            'modules' => serialize([['mod' => 0, 'col' => 'main']]),
        ]);

        $layoutAdapter = $this->createAdapterMock(['findById']);
        $layoutAdapter
            ->expects($this->once())
            ->method('findById')
            ->willReturn($layout)
        ;

        $framework = $this->createContaoFrameworkMock([LayoutModel::class => $layoutAdapter]);
        $framework
            ->expects($this->once())
            ->method('createInstance')
            ->with(PageModel::class)
            ->willReturn($page)
        ;

        $pageRegistry = $this->createMock(PageRegistry::class);
        $pageRegistry
            ->expects($this->once())
            ->method('supportsContentComposition')
            ->with($page)
            ->willReturn(true)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->never())
            ->method('insert')
        ;

        $connection
            ->expects($this->once())
            ->method('fetchOne')
            ->with('SELECT COUNT(*) FROM tl_article WHERE pid = :pid')
            ->willReturn(0)
        ;

        $dc = $this->createClassWithPropertiesStub(DC_Table::class, ['id' => 17, 'table' => 'tl_foo']);
        $dc
            ->method('getCurrentRecord')
            ->willReturn($this->pageRecord)
        ;

        $listener = $this->getListener($framework, pageRegistry: $pageRegistry, connection: $connection, requestStack: $requestStack);
        $listener->generateArticleForPage($dc);
    }

    public function testGenerateArticleForNewPage(): void
    {
        $this->security
            ->expects($this->once())
            ->method('isGranted')
            ->with(ContaoCorePermissions::DC_PREFIX.'tl_article')
            ->willReturn(true)
        ;

        $requestStack = $this->createMock(RequestStack::class);

        $this->expectRequest($requestStack, true, ['tl_foo' => [17]]);
        $this->expectUser();

        $page = $this->mockPageWithRow();

        $layout = $this->createClassWithPropertiesStub(LayoutModel::class, [
            'modules' => serialize([['mod' => 0, 'col' => 'main']]),
        ]);

        $layoutAdapter = $this->createAdapterMock(['findById']);
        $layoutAdapter
            ->expects($this->once())
            ->method('findById')
            ->willReturn($layout)
        ;

        $framework = $this->createContaoFrameworkMock([LayoutModel::class => $layoutAdapter]);
        $framework
            ->expects($this->once())
            ->method('createInstance')
            ->with(PageModel::class)
            ->willReturn($page)
        ;

        $pageRegistry = $this->createMock(PageRegistry::class);
        $pageRegistry
            ->expects($this->once())
            ->method('supportsContentComposition')
            ->with($page)
            ->willReturn(true)
        ;

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

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('insert')
            ->with('tl_article', $article)
        ;

        $connection
            ->expects($this->once())
            ->method('fetchOne')
            ->with('SELECT COUNT(*) FROM tl_article WHERE pid = :pid')
            ->willReturn(0)
        ;

        $dc = $this->createClassWithPropertiesStub(DC_Table::class, ['id' => 17, 'table' => 'tl_foo']);
        $dc
            ->method('getCurrentRecord')
            ->willReturn($this->pageRecord)
        ;

        $listener = $this->getListener($framework, pageRegistry: $pageRegistry, connection: $connection, requestStack: $requestStack);
        $listener->generateArticleForPage($dc);
    }

    #[DataProvider('moduleConfigProvider')]
    public function testUsesTheLayoutColumnForNewArticle(array $modules, string $expectedColumn): void
    {
        $this->security
            ->expects($this->once())
            ->method('isGranted')
            ->with(ContaoCorePermissions::DC_PREFIX.'tl_article')
            ->willReturn(true)
        ;

        $requestStack = $this->createMock(RequestStack::class);

        $this->expectRequest($requestStack, true, ['tl_foo' => [17]]);
        $this->expectUser();

        $page = $this->mockPageWithRow();

        $pageRegistry = $this->createMock(PageRegistry::class);
        $pageRegistry
            ->expects($this->once())
            ->method('supportsContentComposition')
            ->with($page)
            ->willReturn(true)
        ;

        $layout = $this->createClassWithPropertiesStub(LayoutModel::class, ['modules' => serialize($modules)]);

        $layoutAdapter = $this->createAdapterMock(['findById']);
        $layoutAdapter
            ->expects($this->once())
            ->method('findById')
            ->willReturn($layout)
        ;

        $framework = $this->createContaoFrameworkMock([LayoutModel::class => $layoutAdapter]);
        $framework
            ->expects($this->once())
            ->method('createInstance')
            ->with(PageModel::class)
            ->willReturn($page)
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

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('insert')
            ->with('tl_article', $article)
        ;

        $connection
            ->expects($this->once())
            ->method('fetchOne')
            ->with('SELECT COUNT(*) FROM tl_article WHERE pid = :pid')
            ->willReturn(0)
        ;

        $dc = $this->createClassWithPropertiesStub(DC_Table::class, ['id' => 17, 'table' => 'tl_foo']);
        $dc
            ->method('getCurrentRecord')
            ->willReturn($this->pageRecord)
        ;

        $listener = $this->getListener($framework, pageRegistry: $pageRegistry, connection: $connection, requestStack: $requestStack);
        $listener->generateArticleForPage($dc);
    }

    public static function moduleConfigProvider(): iterable
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
        $user = $this->createClassWithPropertiesStub(BackendUser::class, ['id' => 1]);

        $this->security
            ->expects($this->atLeastOnce())
            ->method('getUser')
            ->willReturn($user)
        ;
    }

    private function expectRequest(MockObject|RequestStack $requestStack, bool $hasSession, array|null $newRecords = null): void
    {
        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('hasSession')
            ->willReturn($hasSession)
        ;

        if (null === $newRecords) {
            $session = $this->createStub(SessionInterface::class);
        } else {
            $sessionBag = $this->createMock(AttributeBagInterface::class);
            $sessionBag
                ->expects($this->once())
                ->method('get')
                ->with('new_records')
                ->willReturn($newRecords)
            ;

            $session = $this->createMock(SessionInterface::class);
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

        $requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request)
        ;
    }

    private function mockPageWithRow(): PageModel&MockObject
    {
        $page = $this->createClassWithPropertiesMock(PageModel::class, $this->pageRecord);
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

        return $page;
    }

    private function getListener(ContaoFramework|null $framework = null, PageRegistry|null $pageRegistry = null, Connection|null $connection = null, RequestStack|null $requestStack = null, UrlGeneratorInterface|null $urlGenerator = null): ContentCompositionListener
    {
        $framework ??= $this->createContaoFrameworkStub([PageModel::class => $this->createAdapterStub(['findById'])]);
        $pageRegistry ??= $this->createStub(PageRegistry::class);
        $connection ??= $this->createStub(Connection::class);
        $requestStack ??= $this->createStub(RequestStack::class);
        $urlGenerator ??= $this->createStub(UrlGeneratorInterface::class);

        return new ContentCompositionListener($framework, $this->security, $pageRegistry, $connection, $requestStack, $urlGenerator);
    }
}
