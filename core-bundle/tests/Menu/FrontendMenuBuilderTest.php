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
    public function testBuildsTheMenu(): void
    {
        $rootId = 1;
        $pages = [
            [
                'id' => $rootId,
                'pid' => 0,
                'type' => 'root',
                'title' => 'Personal homepage',
                'pageTitle' => 'Personal homepage',
                'published' => true,
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
            ],
            [
                'id' => 8,
                'pid' => 4,
                'type' => 'regular',
                'title' => 'Imprint',
                'pageTitle' => 'Imprint',
                'alias' => 'imprint',
                'robots' => '',
                'published' => true,
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
            ],
        ];

        $menuFactory = new MenuFactory();
        $root = $menuFactory->createItem('root');

        $menuBuilder = new FrontendMenuBuilder(
            $menuFactory,
            $this->mockRequestStack(),
            $this->mockEventDispatcher(),
            $this->mockConnection($pages),
            $this->mockPageRegistry(),
            $this->mockPageModelAdapter($pages),
            $this->mockTokenChecker(),
            $this->mockSecurity(),
            $this->createMock(LoggerInterface::class),
            $this->createMock(Database::class)
        );

        $tree = $menuBuilder->getMenu($root, $rootId);

        $this->assertSame('root', $tree->getName());
        $this->assertNotNull($tree->getChild('Home'));
        $this->assertNull($tree->getChild('Member area'));
        $this->assertSame(StringUtil::encodeEmail('mailto:me@example.org'), $tree->getChild('Write me')->getUri());
        $this->assertSame($tree->getChild('Contact')->getUri(), $tree->getChild('⏭')->getUri());

        $item = $tree->getChild('Contact');
        $this->assertTrue(\in_array('blue', explode(' ', $item->getExtra('class')), true));
        $this->assertTrue(\in_array('nofollow', explode(' ', $item->getLinkAttribute('rel')), true));
        $this->assertSame('contact', $item->getExtra('alias'));
        $this->assertInstanceOf(PageModel::class, $item->getExtra('pageModel'));
        $this->assertSame('Contact', $item->getLinkAttribute('title'));
        $this->assertSame('c', $item->getLinkAttribute('accesskey'));
        $this->assertSame('-1', $item->getLinkAttribute('tabindex'));
        $this->assertTrue($item->hasChildren());
        $this->assertTrue($item->getDisplayChildren());

        $item = $tree->getChild('Redirected');
        $this->assertTrue(\in_array('noreferrer', explode(' ', $item->getLinkAttribute('rel')), true));
        $this->assertSame('_blank', $item->getLinkAttribute('target'));
    }

    public function testShowsProtectedIfConfigured(): void
    {
        $rootId = 1;
        $pages = [
            [
                'id' => $rootId,
                'pid' => 0,
                'type' => 'root',
                'title' => 'Personal homepage',
                'pageTitle' => 'Personal homepage',
                'published' => true,
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
            ],
        ];

        $menuFactory = new MenuFactory();
        $root = $menuFactory->createItem('root');

        $menuBuilder = new FrontendMenuBuilder(
            $menuFactory,
            $this->mockRequestStack(),
            $this->mockEventDispatcher(),
            $this->mockConnection($pages),
            $this->mockPageRegistry(),
            $this->mockPageModelAdapter($pages),
            $this->mockTokenChecker(),
            $this->mockSecurity(),
            $this->createMock(LoggerInterface::class),
            $this->createMock(Database::class)
        );

        $tree = $menuBuilder->getMenu($root, $rootId, 1, null, ['showProtected' => true]);
        $item = $tree->getChild('Member area');

        $this->assertNotNull($item);
        $this->assertTrue(\in_array('protected', explode(' ', $item->getExtra('class')), true));
    }

    public function testShowsProtectedIfLoggedIn(): void
    {
        $rootId = 1;
        $pages = [
            [
                'id' => $rootId,
                'pid' => 0,
                'type' => 'root',
                'title' => 'Personal homepage',
                'pageTitle' => 'Personal homepage',
                'published' => true,
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
            ],
        ];

        $menuFactory = new MenuFactory();
        $root = $menuFactory->createItem('root');

        $menuBuilder = new FrontendMenuBuilder(
            $menuFactory,
            $this->mockRequestStack(),
            $this->mockEventDispatcher(),
            $this->mockConnection($pages),
            $this->mockPageRegistry(),
            $this->mockPageModelAdapter($pages),
            $this->mockTokenChecker(),
            $this->mockSecurity(true, true),
            $this->createMock(LoggerInterface::class),
            $this->createMock(Database::class)
        );

        $tree = $menuBuilder->getMenu($root, $rootId);
        $item = $tree->getChild('Member area');

        $this->assertNotNull($item);
        $this->assertTrue(\in_array('protected', explode(' ', $item->getExtra('class')), true));
    }

    public function testBuildsTheMenuWithActivePage(): void
    {
        $rootId = 1;
        $pages = [
            [
                'id' => $rootId,
                'pid' => 0,
                'type' => 'root',
                'title' => 'Personal homepage',
                'pageTitle' => 'Personal homepage',
                'published' => true,
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
            ],
            [
                'id' => 4,
                'pid' => 1,
                'type' => 'regular',
                'title' => 'Contact',
                'pageTitle' => 'Contact',
                'alias' => 'contact',
                'trail' => [1],
                'robots' => '',
                'published' => true,
            ],
        ];

        $requestPage = $this->createMock(PageModel::class);
        $requestPage
            ->method('__get')
            ->willReturnCallback(static fn (string $property) => $pages[2][$property] ?? null)
        ;

        $menuFactory = new MenuFactory();
        $root = $menuFactory->createItem('root');

        $menuBuilder = new FrontendMenuBuilder(
            $menuFactory,
            $this->mockRequestStack($requestPage),
            $this->mockEventDispatcher(),
            $this->mockConnection($pages),
            $this->mockPageRegistry(),
            $this->mockPageModelAdapter($pages),
            $this->mockTokenChecker(),
            $this->mockSecurity(),
            $this->createMock(LoggerInterface::class),
            $this->createMock(Database::class)
        );

        $tree = $menuBuilder->getMenu($root, $rootId);

        $this->assertTrue(\in_array('sibling', explode(' ', $tree->getChild('Home')->getExtra('class')), true));

        $item = $tree->getChild('Contact');
        $this->assertTrue($item->getExtra('isActive'));
        $this->assertTrue($item->isCurrent());
        $this->assertTrue(\in_array('active', explode(' ', $item->getExtra('class')), true));
    }

    public function testHidesSubmenuWithHardLimit(): void
    {
        $rootId = 1;
        $pages = [
            [
                'id' => $rootId,
                'pid' => 0,
                'type' => 'root',
                'title' => 'Personal homepage',
                'pageTitle' => 'Personal homepage',
                'published' => true,
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
            ],
            [
                'id' => 4,
                'pid' => 1,
                'type' => 'regular',
                'title' => 'Contact',
                'pageTitle' => 'Contact',
                'alias' => 'contact',
                'trail' => [2],
                'robots' => '',
                'published' => true,
            ],
            [
                'id' => 5,
                'pid' => 4,
                'type' => 'regular',
                'title' => 'Imprint',
                'pageTitle' => 'Imprint',
                'alias' => 'imprint',
                'trail' => [1, 4],
                'robots' => '',
                'published' => true,
            ],
        ];

        $requestPage = $this->createMock(PageModel::class);
        $requestPage
            ->method('__get')
            ->willReturnCallback(static fn (string $property) => $pages[2][$property] ?? null)
        ;

        $menuFactory = new MenuFactory();
        $root = $menuFactory->createItem('root');

        $menuBuilder = new FrontendMenuBuilder(
            $menuFactory,
            $this->mockRequestStack($requestPage),
            $this->mockEventDispatcher(),
            $this->mockConnection($pages),
            $this->mockPageRegistry(),
            $this->mockPageModelAdapter($pages),
            $this->mockTokenChecker(),
            $this->mockSecurity(),
            $this->createMock(LoggerInterface::class),
            $this->createMock(Database::class)
        );

        $tree = $menuBuilder->getMenu($root, $rootId, 1, null, ['showLevel' => 1, 'hardLimit' => true]);

        $item = $tree->getChild('Contact');
        $this->assertCount(1, $item->getChildren());
        $this->assertFalse($item->getDisplayChildren());
    }

    public function testBuildsCustomNav(): void
    {
        $pages = [
            [
                'id' => 1,
                'pid' => 0,
                'type' => 'root',
                'title' => 'Personal homepage',
                'pageTitle' => 'Personal homepage',
                'alias' => 'personal-homepage',
                'robots' => '',
                'published' => true,
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
            ],
            [
                'id' => 4,
                'pid' => 1,
                'type' => 'regular',
                'title' => 'Contact',
                'pageTitle' => 'Contact',
                'alias' => 'contact',
                'robots' => 'noindex,nofollow',
                'published' => true,
            ],
            [
                'id' => 8,
                'pid' => 4,
                'type' => 'regular',
                'title' => 'Imprint',
                'pageTitle' => 'Imprint',
                'alias' => 'imprint',
                'robots' => '',
                'published' => true,
            ],
        ];

        $menuFactory = new MenuFactory();
        $root = $menuFactory->createItem('root');

        $menuBuilder = new FrontendMenuBuilder(
            $menuFactory,
            $this->mockRequestStack(),
            $this->mockEventDispatcher(),
            $this->mockConnection($pages),
            $this->mockPageRegistry(),
            $this->mockPageModelAdapter($pages),
            $this->mockTokenChecker(),
            $this->mockSecurity(),
            $this->createMock(LoggerInterface::class),
            $this->createMock(Database::class)
        );

        $tree = $menuBuilder->getMenu($root, 0, 1, null, ['pages' => array_column($pages, 'id')]);

        $this->assertSame('root', $tree->getName());
        $this->assertNotNull($tree->getChild('Personal homepage'));
        $this->assertNotNull($tree->getChild('Home'));
        $this->assertNotNull($tree->getChild('Contact'));
        $this->assertNotNull($tree->getChild('Imprint'));
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

    private function mockConnection(array $pages): Connection
    {
        $pagesByPid = static fn (int $pid) => array_map(
            static fn (array $page) => [
                'id' => $page['id'],
                'hasSubpages' => \in_array($page['id'], array_column($pages, 'pid'), true),
            ],
            array_filter($pages, static fn (array $p) => $pid === $p['pid'])
        );

        $result = $this->createMock(Result::class);
        $result
            ->method('fetchAllAssociative')
            ->willReturnOnConsecutiveCalls($pagesByPid(1), $pagesByPid(4))
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
    private function mockPageModelAdapter(array $pages): Adapter
    {
        $pageModelAdapter = $this->mockAdapter(['findMultipleByIds', 'findByPk', 'findPublishedById', 'findFirstPublishedRegularByPid', 'findPublishedRegularByIds']);

        $findCallback = function ($id) use ($pages) {
            $page = $pages[array_search($id, array_column($pages, 'id'), true)];

            return $this->mockPageModel($page);
        };

        $pageModelAdapter
            ->method('findMultipleByIds')
            ->willReturn([])
        ;
        $pageModelAdapter
            ->method('findPublishedRegularByIds')
            ->willReturn(array_map(fn (array $page) => $this->mockPageModel($page), $pages))
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
