<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener\Menu;

use Contao\BackendUser;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\Event\MenuEvent;
use Contao\CoreBundle\EventListener\Menu\BackendFavoritesListener;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\DBAL\Connection;
use Knp\Menu\MenuFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class BackendFavoritesListenerTest extends TestCase
{
    public function testDoesNothingIfThereIsNoBackendUser(): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($this->createMock(UserInterface::class))
        ;

        $event = $this->createMock(MenuEvent::class);
        $event
            ->expects($this->never())
            ->method('getTree')
        ;

        $listener = new BackendFavoritesListener(
            $security,
            $this->createMock(RouterInterface::class),
            $this->createMock(RequestStack::class),
            $this->createMock(Connection::class),
            $this->createMock(TranslatorInterface::class),
            $this->createMock(ContaoCsrfTokenManager::class)
        );

        $listener($event);
    }

    /**
     * @dataProvider getCollapsedStatus
     */
    public function testAddsTheMainMenu(bool $collapsed): void
    {
        $user = $this->mockClassWithProperties(BackendUser::class, ['id' => 2]);

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->willReturn('/contao?do=pages&mtg=favorites&ref=foobar')
        ;

        $session = $this->mockSession();

        /** @var AttributeBagInterface $bag */
        $bag = $session->getBag('contao_backend');
        $bag->set('backend_modules', ['favorites' => $collapsed ? 0 : null]);

        $request = Request::create('https://localhost/contao?do=pages&act=edit&id=3');
        $request->attributes->set('_contao_referer_id', 'foobar');
        $request->setSession($session);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->exactly(3))
            ->method('fetchAllAssociative')
            ->willReturnOnConsecutiveCalls(
                [
                    [
                        'id' => 7,
                        'pid' => 0,
                        'tstamp' => 1671538397,
                        'title' => 'Edit page 3',
                        'url' => '/contao?do=pages&act=edit&id=3',
                    ],
                    [
                        'id' => 8,
                        'pid' => 7,
                        'tstamp' => 1671538402,
                        'title' => 'Edit fe_page',
                        'url' => '/contao?do=tpl_editor&act=source&id=templates%2Ffe_page.html5',
                    ],
                ],
                [],
                [],
            )
        ;

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->exactly(2))
            ->method('trans')
            ->willReturnOnConsecutiveCalls('Favorites', $collapsed ? 'Expand node' : 'Collapse node')
        ;

        $factory = new MenuFactory();

        $tree = $factory->createItem('mainMenu');
        $tree->addChild($factory->createItem('content'));

        $event = new MenuEvent($factory, $tree);

        $listener = new BackendFavoritesListener(
            $security,
            $router,
            $requestStack,
            $connection,
            $translator,
            $this->createMock(ContaoCsrfTokenManager::class)
        );

        $listener($event);

        $children = array_values($tree->getChildren());

        $this->assertCount(2, $children);
        $this->assertSame('favorites', $children[0]->getName());
        $this->assertSame('Favorites', $children[0]->getLabel());
        $this->assertSame(['id' => 'favorites'], $children[0]->getChildrenAttributes());
        $this->assertSame('/contao?do=pages&mtg=favorites&ref=foobar', $children[0]->getUri());

        $linkAttributes = [
            'class' => 'group-favorites',
            'title' => $collapsed ? 'Expand node' : 'Collapse node',
            'data-action' => 'contao--toggle-navigation#toggle:prevent',
            'data-contao--toggle-navigation-category-param' => 'favorites',
            'aria-controls' => 'favorites',
        ];

        if (!$collapsed) {
            $linkAttributes['aria-expanded'] = 'true';
        }

        $this->assertSame($linkAttributes, $children[0]->getLinkAttributes());

        $grandChildren = array_values($children[0]->getChildren());

        $this->assertCount(2, $grandChildren);
        $this->assertSame('favorite_7', $grandChildren[0]->getName());
        $this->assertSame('Edit page 3', $grandChildren[0]->getLabel());
        $this->assertSame('/contao?do=pages&act=edit&id=3&ref=foobar', $grandChildren[0]->getUri());

        $this->assertSame(
            [
                'class' => 'navigation',
                'title' => 'Edit page 3',
            ],
            $grandChildren[0]->getLinkAttributes()
        );

        $this->assertSame('favorite_8', $grandChildren[1]->getName());
        $this->assertSame('Edit fe_page', $grandChildren[1]->getLabel());
        $this->assertSame('/contao?do=tpl_editor&act=source&id=templates%2Ffe_page.html5&ref=foobar', $grandChildren[1]->getUri());

        $this->assertSame(
            [
                'class' => 'navigation',
                'title' => 'Edit fe_page',
            ],
            $grandChildren[1]->getLinkAttributes()
        );

        $this->assertSame('content', $children[1]->getName());
    }

    public function getCollapsedStatus(): \Generator
    {
        yield [false];
        yield [true];
    }

    public function testDoesNotAddTheMainMenuIfThereIsNoRequest(): void
    {
        $user = $this->mockClassWithProperties(BackendUser::class, ['id' => 2]);

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $factory = new MenuFactory();

        $event = $this->createMock(MenuEvent::class);
        $event
            ->expects($this->once())
            ->method('getTree')
            ->willReturn($factory->createItem('mainMenu'))
        ;

        $event
            ->expects($this->never())
            ->method('getFactory')
        ;

        $listener = new BackendFavoritesListener(
            $security,
            $this->createMock(RouterInterface::class),
            $this->createMock(RequestStack::class),
            $this->createMock(Connection::class),
            $this->createMock(TranslatorInterface::class),
            $this->createMock(ContaoCsrfTokenManager::class)
        );

        $listener($event);
    }

    public function testDoesNotAddTheMainMenuIfThereAreNoChildren(): void
    {
        $user = $this->mockClassWithProperties(BackendUser::class, ['id' => 2]);

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->willReturn('/contao?do=pages&mtg=favorites&ref=foobar')
        ;

        $session = $this->mockSession();

        $request = Request::create('https://localhost/contao?do=pages&act=edit&id=3');
        $request->attributes->set('_contao_referer_id', 'foobar');
        $request->setSession($session);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchAllAssociative')
            ->willReturn([])
        ;

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->exactly(2))
            ->method('trans')
            ->willReturnOnConsecutiveCalls('Favorites', 'Collapse node')
        ;

        $factory = new MenuFactory();

        $tree = $factory->createItem('mainMenu');
        $tree->addChild($factory->createItem('content'));

        $event = new MenuEvent($factory, $tree);

        $listener = new BackendFavoritesListener(
            $security,
            $router,
            $requestStack,
            $connection,
            $translator,
            $this->createMock(ContaoCsrfTokenManager::class)
        );

        $listener($event);

        $children = array_values($tree->getChildren());

        $this->assertCount(1, $children);
        $this->assertSame('content', $children[0]->getName());
    }

    public function testAddsTheHeaderMenu(): void
    {
        $user = $this->mockClassWithProperties(BackendUser::class, ['id' => 2]);

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->willReturn('/contao?do=favorites&act=paste&mode=create&data=&rt=foo&ref=bar')
        ;

        $request = Request::create('https://localhost/contao?do=pages&act=edit&id=3');
        $request->attributes->set('_contao_referer_id', 'foobar');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchOne')
            ->with('SELECT COUNT(*) FROM tl_favorites WHERE url = :url AND user = :user')
            ->willReturn(0)
        ;

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->once())
            ->method('trans')
            ->with('MSC.favorite', [], 'contao_default')
            ->willReturn('Save URL as favorite')
        ;

        $tokenManager = $this->createMock(ContaoCsrfTokenManager::class);
        $tokenManager
            ->expects($this->once())
            ->method('getDefaultTokenValue')
            ->willReturn('foobar')
        ;

        $factory = new MenuFactory();

        $tree = $factory->createItem('headerMenu');
        $tree->addChild($factory->createItem('manual'));
        $tree->addChild($factory->createItem('alerts'));

        $event = new MenuEvent($factory, $tree);

        $listener = new BackendFavoritesListener(
            $security,
            $router,
            $requestStack,
            $connection,
            $translator,
            $tokenManager
        );

        $listener($event);

        $children = $tree->getChildren();

        $this->assertSame(['manual', 'favorite', 'alerts'], array_keys($tree->getChildren()));
        $this->assertSame('favorite', $children['favorite']->getName());
        $this->assertSame('Save URL as favorite', $children['favorite']->getLabel());
        $this->assertTrue($children['favorite']->getExtra('safe_label'));
        $this->assertSame('/contao?do=favorites&act=paste&mode=create&data=&rt=foo&ref=bar', $children['favorite']->getUri());

        $linkAttributes = [
            'class' => 'icon-favorite',
            'title' => 'Save URL as favorite',
        ];

        $this->assertSame($linkAttributes, $children['favorite']->getLinkAttributes());
    }

    public function testAddsAnEditFavoritesLinkIfTheUrlIsAFavoriteAlready(): void
    {
        $user = $this->mockClassWithProperties(BackendUser::class, ['id' => 2]);

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->willReturn('/contao?do=favorites&ref=bar')
        ;

        $request = Request::create('https://localhost/contao?do=pages&act=edit&id=3');
        $request->attributes->set('_contao_referer_id', 'foobar');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchOne')
            ->with('SELECT COUNT(*) FROM tl_favorites WHERE url = :url AND user = :user')
            ->willReturn(1)
        ;

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->once())
            ->method('trans')
            ->with('MSC.editFavorites', [], 'contao_default')
            ->willReturn('Edit favorites')
        ;

        $factory = new MenuFactory();

        $tree = $factory->createItem('headerMenu');
        $tree->addChild($factory->createItem('manual'));

        $event = new MenuEvent($factory, $tree);

        $listener = new BackendFavoritesListener(
            $security,
            $router,
            $requestStack,
            $connection,
            $translator,
            $this->createMock(ContaoCsrfTokenManager::class)
        );

        $listener($event);

        $children = $tree->getChildren();

        $this->assertSame(['manual', 'favorite'], array_keys($tree->getChildren()));
        $this->assertSame('favorite', $children['favorite']->getName());
        $this->assertSame('Edit favorites', $children['favorite']->getLabel());
        $this->assertTrue($children['favorite']->getExtra('safe_label'));
        $this->assertSame('/contao?do=favorites&ref=bar', $children['favorite']->getUri());

        $linkAttributes = [
            'class' => 'icon-favorite icon-favorite--active',
            'title' => 'Edit favorites',
        ];

        $this->assertSame($linkAttributes, $children['favorite']->getLinkAttributes());
    }
}
