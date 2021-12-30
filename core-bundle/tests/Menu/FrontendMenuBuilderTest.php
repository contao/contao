<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Menu;

use Contao\CoreBundle\Event\FrontendMenuEvent;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Menu\FrontendMenuBuilder;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Database;
use Contao\PageModel;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Knp\Menu\MenuFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;

class FrontendMenuBuilderTest extends TestCase
{
    private const ROOT_ID = 1;
    private const PAGES = [
        [
            'id' => 1,
            'pid' => 0,
            'type' => 'root',
            'title' => 'Personal homepage',
            'pageTitle' => 'Personal homepage',
            'alias' => 'personal-homepage',
            'robots' => '',
            'published' => true,
            'sitemap' => '',
            'trail' => [],
        ],
        [
            'id' => 2,
            'pid' => 1,
            'type' => 'regular',
            'title' => 'Home',
            'pageTitle' => 'Home',
            'alias' => 'index',
            'robots' => '',
            'published' => true,
            'sitemap' => '',
            'trail' => [1],
        ],
        [
            'id' => 3,
            'pid' => 1,
            'type' => 'regular',
            'title' => 'Member area',
            'pageTitle' => 'Member area',
            'alias' => 'member-area',
            'protected' => true,
            'groups' => [179],
            'robots' => '',
            'published' => true,
            'sitemap' => '',
            'trail' => [1],
        ],
        [
            'id' => 4,
            'pid' => 1,
            'type' => 'regular',
            'title' => 'Contact',
            'pageTitle' => 'Contact',
            'alias' => 'contact',
            'accesskey' => 'c',
            'tabindex' => '-1',
            'cssClass' => 'blue red',
            'robots' => 'noindex,nofollow',
            'published' => true,
            'sitemap' => '',
            'trail' => [1],
        ],
        [
            // Second level
            'id' => 8,
            'pid' => 4,
            'type' => 'regular',
            'title' => 'Imprint',
            'pageTitle' => 'Imprint',
            'alias' => 'imprint',
            'robots' => '',
            'published' => true,
            'sitemap' => '',
            'trail' => [1, 4],
        ],
        [
            // Third level
            'id' => 9,
            'pid' => 8,
            'type' => 'regular',
            'title' => 'Privacy notice',
            'pageTitle' => 'Privacy notice',
            'alias' => 'privacy',
            'robots' => '',
            'published' => true,
            'sitemap' => '',
            'trail' => [1, 4, 8],
        ],
        [
            'id' => 5,
            'pid' => 1,
            'type' => 'redirect',
            'title' => 'Write me',
            'pageTitle' => 'Write me',
            'alias' => 'write-me',
            'url' => 'mailto:me@example.org',
            'robots' => '',
            'published' => true,
            'sitemap' => '',
            'trail' => [1],
        ],
        [
            'id' => 7,
            'pid' => 1,
            'type' => 'redirect',
            'title' => 'Redirected',
            'pageTitle' => 'Redirected',
            'alias' => 'Redirected',
            'url' => '',
            'target' => 4,
            'robots' => '',
            'published' => true,
            'sitemap' => '',
            'trail' => [1],
        ],
        [
            'id' => 6,
            'pid' => 1,
            'type' => 'forward',
            'title' => '⏭',
            'pageTitle' => '⏭',
            'alias' => 'forward',
            'jumpTo' => 4,
            'robots' => '',
            'published' => true,
            'sitemap' => '',
            'trail' => [1],
        ],
        [
            'id' => 10,
            'pid' => 1,
            'type' => 'regular',
            'title' => 'Sitemap',
            'pageTitle' => 'Sitemap',
            'alias' => 'sitemap',
            'robots' => '',
            'published' => true,
            'sitemap' => 'map_never',
            'trail' => [1],
        ],
    ];

    public function testBuildsTheMenu(): void
    {
        $menuFactory = new MenuFactory();
        $root = $menuFactory->createItem('root');

        $menuBuilder = new FrontendMenuBuilder(
            $menuFactory,
            $this->mockRequestStack(),
            $this->mockEventDispatcher(),
            $this->mockConnection(),
            $this->mockPageRegistry(),
            $this->mockPageModelAdapter(),
            $this->mockTokenChecker(),
            $this->mockSecurity(),
            $this->createMock(LoggerInterface::class),
            $this->mockDatabase()
        );

        $tree = $menuBuilder->getMenu($root, self::ROOT_ID);

        // Assert root item exists
        $this->assertSame('root', $tree->getName());

        // Assert item are added to the tree
        $this->assertNotNull($tree->getChild('Home'));
        $this->assertNotNull($tree->getChild('Sitemap'));

        // Assert protected pages are hidden
        $this->assertNull($tree->getChild('Member area'));

        // Assert redirect pages have mailto addresses encoded
        $this->assertSame(StringUtil::encodeEmail('mailto:me@example.org'), $tree->getChild('Write me')->getUri());

        // Assert forward URIs are generated
        $this->assertSame($tree->getChild('Contact')->getUri(), $tree->getChild('⏭')->getUri());

        $item = $tree->getChild('Contact');

        // Assert page css classes are generated
        $this->assertTrue(\in_array('blue', explode(' ', $item->getExtra('class')), true));

        // Assert rel attributes are set for robots=noindex
        $this->assertTrue(\in_array('nofollow', explode(' ', $item->getLinkAttribute('rel')), true));

        // Assert extra properties are set on the menu item
        $this->assertSame('contact', $item->getExtra('alias'));

        // Assert PageModel attribute is set on the menu item
        $this->assertInstanceOf(PageModel::class, $item->getExtra('pageModel'));

        // Assert the title link attribute is set on the menu item
        $this->assertSame('Contact', $item->getLinkAttribute('title'));

        // Assert the accesskey link attribute is set on the menu item
        $this->assertSame('c', $item->getLinkAttribute('accesskey'));

        // Assert the tabindex link attribute is set on the menu item
        $this->assertSame('-1', $item->getLinkAttribute('tabindex'));

        // Assert submenu is generated
        $this->assertTrue($item->hasChildren());
        $this->assertTrue($item->getDisplayChildren());

        $item = $tree->getChild('Redirected');

        // Assert rel and target attributes are set for redirect pages
        $this->assertTrue(\in_array('noreferrer', explode(' ', $item->getLinkAttribute('rel')), true));
        $this->assertSame('_blank', $item->getLinkAttribute('target'));
    }

    public function testShowsProtectedPagesIfConfigured(): void
    {
        $menuFactory = new MenuFactory();
        $root = $menuFactory->createItem('root');

        $menuBuilder = new FrontendMenuBuilder(
            $menuFactory,
            $this->mockRequestStack(),
            $this->mockEventDispatcher(),
            $this->mockConnection(),
            $this->mockPageRegistry(),
            $this->mockPageModelAdapter(),
            $this->mockTokenChecker(),
            $this->mockSecurity(),
            $this->createMock(LoggerInterface::class),
            $this->mockDatabase()
        );

        // Configure showProtected=true
        $tree = $menuBuilder->getMenu($root, self::ROOT_ID, 1, null, ['showProtected' => true]);

        // Assert protected page is added to the menu
        $item = $tree->getChild('Member area');
        $this->assertNotNull($item);
        $this->assertTrue(\in_array('protected', explode(' ', $item->getExtra('class')), true));
    }

    public function testShowsProtectedPagesIfLoggedIn(): void
    {
        $menuFactory = new MenuFactory();
        $root = $menuFactory->createItem('root');

        $menuBuilder = new FrontendMenuBuilder(
            $menuFactory,
            $this->mockRequestStack(),
            $this->mockEventDispatcher(),
            $this->mockConnection(),
            $this->mockPageRegistry(),
            $this->mockPageModelAdapter(),
            $this->mockTokenChecker(),
            // Configure security to grant access on member group
            $this->mockSecurity(true, true),
            $this->createMock(LoggerInterface::class),
            $this->mockDatabase()
        );

        $tree = $menuBuilder->getMenu($root, 1);

        // Assert protected page is added to the menu
        $item = $tree->getChild('Member area');
        $this->assertNotNull($item);
        $this->assertTrue(\in_array('protected', explode(' ', $item->getExtra('class')), true));
    }

    public function testMarksActivePage(): void
    {
        // Configure "Contact" page as current page
        $requestPage = $this->createMock(PageModel::class);
        $requestPage
            ->method('__get')
            ->willReturnCallback(static fn (string $property) => self::PAGES[array_search(4, array_column(self::PAGES, 'id'), true)][$property] ?? null)
        ;

        $menuFactory = new MenuFactory();
        $root = $menuFactory->createItem('root');

        $menuBuilder = new FrontendMenuBuilder(
            $menuFactory,
            $this->mockRequestStack($requestPage),
            $this->mockEventDispatcher(),
            $this->mockConnection(),
            $this->mockPageRegistry(),
            $this->mockPageModelAdapter(),
            $this->mockTokenChecker(),
            $this->mockSecurity(),
            $this->createMock(LoggerInterface::class),
            $this->mockDatabase()
        );

        $tree = $menuBuilder->getMenu($root, self::ROOT_ID);

        // Assert request page is marked current
        $item = $tree->getChild('Contact');
        $this->assertTrue($item->getExtra('isActive'));
        $this->assertTrue($item->isCurrent());
        $this->assertTrue(\in_array('active', explode(' ', $item->getExtra('class')), true));

        // Assert "sibling" css class is added to non-active pages on the same level
        $item = $tree->getChild('Home');
        $this->assertTrue(\in_array('sibling', explode(' ', $item->getExtra('class')), true));
    }

    public function testHidesSubmenu(): void
    {
        $menuFactory = new MenuFactory();
        $root = $menuFactory->createItem('root');

        $menuBuilder = new FrontendMenuBuilder(
            $menuFactory,
            $this->mockRequestStack(),
            $this->mockEventDispatcher(),
            $this->mockConnection(),
            $this->mockPageRegistry(),
            $this->mockPageModelAdapter(),
            $this->mockTokenChecker(),
            $this->mockSecurity(),
            $this->createMock(LoggerInterface::class),
            $this->mockDatabase()
        );

        // Configure to show only one level and no pages above
        $tree = $menuBuilder->getMenu($root, self::ROOT_ID, 1, null, ['showLevel' => 1]);

        // Assert submenu is generated but not displayed
        $item = $tree->getChild('Contact');
        $this->assertCount(1, $item->getChildren());
        $this->assertFalse($item->getDisplayChildren());
    }

    public function testHidesSubmenuWithHardLimit(): void
    {
        // Configure "Contact" page as current page
        $requestPage = $this->createMock(PageModel::class);
        $requestPage
            ->method('__get')
            ->willReturnCallback(static fn (string $property) => self::PAGES[array_search(4, array_column(self::PAGES, 'id'), true)][$property] ?? null)
        ;

        $menuFactory = new MenuFactory();
        $root = $menuFactory->createItem('root');

        $menuBuilder = new FrontendMenuBuilder(
            $menuFactory,
            $this->mockRequestStack($requestPage),
            $this->mockEventDispatcher(),
            $this->mockConnection(),
            $this->mockPageRegistry(),
            $this->mockPageModelAdapter(),
            $this->mockTokenChecker(),
            $this->mockSecurity(),
            $this->createMock(LoggerInterface::class),
            $this->mockDatabase()
        );

        // Configure to show only one level and no pages above
        $tree = $menuBuilder->getMenu($root, self::ROOT_ID, 1, null, ['showLevel' => 1, 'hardLimit' => true]);

        // Assert submenu is generated but not displayed
        $item = $tree->getChild('Contact');
        $this->assertCount(1, $item->getChildren());
        $this->assertFalse($item->getDisplayChildren());
    }

    public function testShowsSubmenuForActivePage(): void
    {
        // Configure "Imprint" page as current page
        $requestPage = $this->createMock(PageModel::class);
        $requestPage
            ->method('__get')
            ->willReturnCallback(static fn (string $property) => self::PAGES[array_search(8, array_column(self::PAGES, 'id'), true)][$property] ?? null)
        ;

        $menuFactory = new MenuFactory();
        $root = $menuFactory->createItem('root');

        $menuBuilder = new FrontendMenuBuilder(
            $menuFactory,
            $this->mockRequestStack($requestPage),
            $this->mockEventDispatcher(),
            $this->mockConnection(),
            $this->mockPageRegistry(),
            $this->mockPageModelAdapter(),
            $this->mockTokenChecker(),
            $this->mockSecurity(),
            $this->createMock(LoggerInterface::class),
            $this->mockDatabase()
        );

        // Configure to show only one level and no pages above
        $tree = $menuBuilder->getMenu($root, self::ROOT_ID, 1, null, ['showLevel' => 1]);

        // Assert submenu is generated and displayed
        $item = $tree->getChild('Contact');
        $this->assertCount(1, $item->getChildren());
        $this->assertTrue($item->getDisplayChildren());
    }

    public function testBuildsSitemap(): void
    {
        $menuFactory = new MenuFactory();
        $root = $menuFactory->createItem('root');

        $menuBuilder = new FrontendMenuBuilder(
            $menuFactory,
            $this->mockRequestStack(),
            $this->mockEventDispatcher(),
            $this->mockConnection(),
            $this->mockPageRegistry(),
            $this->mockPageModelAdapter(),
            $this->mockTokenChecker(),
            $this->mockSecurity(),
            $this->createMock(LoggerInterface::class),
            $this->mockDatabase()
        );

        $tree = $menuBuilder->getMenu($root, self::ROOT_ID, 1, null, ['isSitemap' => true]);

        // Assert root item exists
        $this->assertSame('root', $tree->getName());

        // Assert item are added to the tree
        $this->assertNotNull($tree->getChild('Home'));

        // Assert sitemap-hidden pages are skipped
        $this->assertNull($tree->getChild('Sitemap'));
    }

    public function testBuildsCustomNav(): void
    {
        $menuFactory = new MenuFactory();
        $root = $menuFactory->createItem('root');

        $menuBuilder = new FrontendMenuBuilder(
            $menuFactory,
            $this->mockRequestStack(),
            $this->mockEventDispatcher(),
            $this->mockConnection(),
            $this->mockPageRegistry(),
            $this->mockPageModelAdapter(),
            $this->mockTokenChecker(),
            $this->mockSecurity(),
            $this->createMock(LoggerInterface::class),
            $this->mockDatabase()
        );

        $tree = $menuBuilder->getMenu($root, 0, 1, null, ['pages' => array_column(self::PAGES, 'id')]);

        // Assert root item exists
        $this->assertSame('root', $tree->getName());

        // Assert every page (incl. root) is in custom nav without hierarchy/levels
        foreach (self::PAGES as $page) {
            if ($page['protected'] ?? false) {
                continue;
            }

            $this->assertNotNull($tree->getChild($page['title']), "{$page['title']} is not generated");
        }
    }

    public function mockDatabase(): Database
    {
        $getChildRecords = static fn (array $ids) => array_filter(self::PAGES, static fn (array $page) => \in_array($page['pid'], $ids, true));

        $database = $this->createMock(Database::class);
        $database
            ->method('getChildRecords')
            ->willReturnCallback(
                static function ($ids, string $table) use ($getChildRecords): array {
                    $ids = (array) $ids;

                    $childRecords = [];

                    do {
                        $ids = array_column($getChildRecords($ids), 'id');
                        $childRecords = array_merge($childRecords, $ids);
                    } while (!empty($ids));

                    return $childRecords;
                }
            )
        ;

        $this->assertSame([8, 9], $database->getChildRecords(4, 'tl_page'));
        $this->assertSame([9], $database->getChildRecords(8, 'tl_page'));

        return $database;
    }

    private function mockTokenChecker(bool $hasBackendUser = false, bool $isPreviewMode = false): TokenChecker
    {
        $mock = $this->createMock(TokenChecker::class);
        $mock
            ->method('hasBackendUser')
            ->willReturn($hasBackendUser)
        ;
        $mock
            ->method('isPreviewMode')
            ->willReturn($isPreviewMode)
        ;

        return $mock;
    }

    private function mockSecurity(bool $isMember = false, bool $isGrantedProtected = false): Security
    {
        if ($isGrantedProtected && !$isMember) {
            throw new \InvalidArgumentException('Must be member if the member is granted access');
        }

        $mock = $this->createMock(Security::class);
        $mock
            ->expects($this->atLeastOnce())
            ->method('isGranted')
            ->willReturnMap([
                ['ROLE_MEMBER', null, $isMember],
                [ContaoCorePermissions::MEMBER_IN_GROUPS, [179], $isGrantedProtected],
            ])
        ;

        return $mock;
    }

    private function mockRequestStack(PageModel $pageModel = null): RequestStack
    {
        $request = new Request();

        if ($pageModel) {
            $request->attributes->set('pageModel', $pageModel);

            $reflection = new \ReflectionProperty(\get_class($request), 'pathInfo');
            $reflection->setAccessible(true);
            $reflection->setValue($request, '/'.$pageModel->alias);
        }

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->atLeastOnce())
            ->method('getCurrentRequest')
            ->willReturn($request)
        ;

        return $requestStack;
    }

    private function mockEventDispatcher(): EventDispatcherInterface
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects($this->atLeastOnce())
            ->method('dispatch')
            ->with($this->isInstanceOf(FrontendMenuEvent::class))
        ;

        return $eventDispatcher;
    }

    private function mockPageRegistry(): PageRegistry
    {
        $pageRegistry = $this->createMock(PageRegistry::class);
        $pageRegistry
            ->method('getUnroutableTypes')
            ->willReturn([])
        ;

        return $pageRegistry;
    }

    private function mockConnection(): Connection
    {
        $pagesByPid = static fn (int $pid) => array_map(
            static fn (array $page) => [
                'id' => $page['id'],
                'hasSubpages' => \in_array($page['id'], array_column(self::PAGES, 'pid'), true),
            ],
            array_filter(self::PAGES, static fn (array $p) => $pid === $p['pid'])
        );

        $result = $this->createMock(Result::class);
        $result
            ->method('fetchAllAssociative')
            // Find the pages by root IDs on consecutive calls
            ->willReturnOnConsecutiveCalls($pagesByPid(1), $pagesByPid(4), $pagesByPid(8))
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('executeQuery')
            ->willReturn($result)
        ;

        return $connection;
    }

    /**
     * @return Adapter<PageModel>
     */
    private function mockPageModelAdapter(): Adapter
    {
        $pageModelAdapter = $this->mockAdapter(['findMultipleByIds', 'findByPk', 'findPublishedById', 'findFirstPublishedRegularByPid', 'findPublishedRegularByIds']);

        $findCallback = function ($id) {
            $page = self::PAGES[array_search($id, array_column(self::PAGES, 'id'), true)];

            return $this->mockPageModel($page);
        };

        $pageModelAdapter
            ->method('findMultipleByIds')
            ->willReturn([])
        ;
        $pageModelAdapter
            ->method('findPublishedRegularByIds')
            ->willReturn(array_map(fn (array $page) => $this->mockPageModel($page), self::PAGES))
        ;
        $pageModelAdapter
            ->method('findByPk')
            ->willReturnCallback($findCallback)
        ;
        $pageModelAdapter
            ->method('findPublishedById')
            ->willReturnCallback($findCallback)
        ;
        $pageModelAdapter
            ->method('findFirstPublishedRegularByPid')
            ->willReturnCallback($findCallback)
        ;

        return $pageModelAdapter;
    }

    private function mockPageModel(array $row): PageModel
    {
        $pageModel = $this->createMock(PageModel::class);
        $pageModel
            ->method('loadDetails')
            ->willReturnSelf()
        ;
        $pageModel
            ->method('getFrontendUrl')
            ->willReturnCallback(static fn () => $row['alias'] ?? null)
        ;
        $pageModel
            ->method('__get')
            ->willReturnCallback(static fn (string $property) => $row[$property] ?? null)
        ;
        $pageModel
            ->method('row')
            ->willReturnCallback(static fn () => $row)
        ;

        return $pageModel;
    }
}
