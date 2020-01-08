<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Tests\EventListener;

use Contao\CoreBundle\Event\MenuEvent;
use Contao\ManagerBundle\EventListener\BackendMenuListener;
use Contao\ManagerBundle\HttpKernel\JwtManager;
use Contao\TestCase\ContaoTestCase;
use Knp\Menu\MenuFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;

class BackendMenuListenerTest extends ContaoTestCase
{
    public function testDoesNothingIfTheUserIsNotAnAdmin(): void
    {
        $event = $this->createMock(MenuEvent::class);
        $event
            ->expects($this->never())
            ->method('getTree')
        ;

        $security = $this->getSecurity(false);
        $router = $this->createMock(RouterInterface::class);

        $listener = new BackendMenuListener($security, $router, new RequestStack(), false, null, null);
        $listener($event);
    }

    /**
     * @dataProvider getItems
     */
    public function testAddsTheDebugButton(string $itemName, array $expect): void
    {
        $request = new Request();
        $request->server->set('QUERY_STRING', 'do=page');
        $request->attributes->set('_contao_referer_id', 'foo');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $params = [
            'do' => 'debug',
            'key' => 'enable',
            'referer' => base64_encode('do=page'),
            'ref' => 'foo',
        ];

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with('contao_backend', $params)
            ->willReturn('/contao?do=debug&key=enable&referer='.base64_encode('do=page').'&ref=foo')
        ;

        $factory = new MenuFactory();
        $item = $factory->createItem($itemName);

        $menu = $factory->createItem('headerMenu');
        $menu->addChild($item);

        $event = new MenuEvent($factory, $menu);
        $jwtManager = $this->createMock(JwtManager::class);
        $security = $this->getSecurity();

        $listener = new BackendMenuListener($security, $router, $requestStack, false, null, $jwtManager);
        $listener($event);

        $children = $event->getTree()->getChildren();

        $this->assertCount(2, $children);
        $this->assertSame($expect, array_keys($children));

        $debug = $children['debug'];

        $this->assertSame('debug_mode', $debug->getLabel());
        $this->assertSame('/contao?do=debug&key=enable&referer=ZG89cGFnZQ==&ref=foo', $debug->getUri());
        $this->assertSame(['class' => 'icon-debug'], $debug->getLinkAttributes());
        $this->assertSame(['translation_domain' => 'ContaoManagerBundle'], $debug->getExtras());
    }

    public function getItems(): \Generator
    {
        yield ['alerts', ['alerts', 'debug']];
        yield ['preview', ['debug', 'preview']];
    }

    public function testDoesNotAddTheDebugButtonIfTheJwtManagerIsNotSet(): void
    {
        $event = $this->createMock(MenuEvent::class);
        $event
            ->expects($this->never())
            ->method('getTree')
        ;

        $security = $this->getSecurity();
        $router = $this->createMock(RouterInterface::class);
        $requestStack = new RequestStack();

        $listener = new BackendMenuListener($security, $router, $requestStack, false, null, null);
        $listener($event);
    }

    public function testDoesNotAddTheDebugButtonIfNotTheHeaderMenu(): void
    {
        $event = $this->createMock(MenuEvent::class);
        $event
            ->expects($this->once())
            ->method('getTree')
            ->willReturn((new MenuFactory())->createItem('mainMenu'))
        ;

        $event
            ->expects($this->never())
            ->method('getFactory')
        ;

        $security = $this->getSecurity();
        $router = $this->createMock(RouterInterface::class);
        $requestStack = new RequestStack();
        $jwtManager = $this->createMock(JwtManager::class);

        $listener = new BackendMenuListener($security, $router, $requestStack, false, null, $jwtManager);
        $listener($event);
    }

    public function testFailsIfTheRequestStackIsEmpty(): void
    {
        $event = $this->createMock(MenuEvent::class);
        $event
            ->expects($this->once())
            ->method('getTree')
            ->willReturn((new MenuFactory())->createItem('headerMenu'))
        ;

        $security = $this->getSecurity();
        $router = $this->createMock(RouterInterface::class);
        $requestStack = new RequestStack();
        $jwtManager = $this->createMock(JwtManager::class);

        $listener = new BackendMenuListener($security, $router, $requestStack, false, null, $jwtManager);

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('The request stack did not contain a request');

        $listener($event);
    }

    public function testAddsTheManagerLink(): void
    {
        $factory = new MenuFactory();
        $system = $factory->createItem('system');

        $menu = $factory->createItem('mainMenu');
        $menu->addChild($system);

        $event = new MenuEvent($factory, $menu);
        $security = $this->getSecurity();
        $router = $this->createMock(RouterInterface::class);
        $requestStack = new RequestStack();

        $listener = new BackendMenuListener($security, $router, $requestStack, false, 'contao-manager.phar.php', null);
        $listener($event);

        $children = $event->getTree()->getChild('system')->getChildren();

        $this->assertCount(1, $children);
        $this->assertArrayHasKey('contao_manager', $children);

        $manager = $children['contao_manager'];

        $this->assertSame('Contao Manager', $manager->getLabel());
        $this->assertSame('/contao-manager.phar.php', $manager->getUri());
        $this->assertSame(['class' => 'navigation contao_manager'], $manager->getLinkAttributes());
    }

    public function testDoesNotAddTheManagerLinkIfTheManagerPathIsEmpty(): void
    {
        $factory = new MenuFactory();
        $system = $factory->createItem('system');

        $menu = $factory->createItem('mainMenu');
        $menu->addChild($system);

        $event = new MenuEvent($factory, $menu);
        $security = $this->getSecurity();
        $router = $this->createMock(RouterInterface::class);
        $requestStack = new RequestStack();

        $listener = new BackendMenuListener($security, $router, $requestStack, false, null, null);
        $listener($event);

        $this->assertCount(0, $event->getTree()->getChild('system')->getChildren());
    }

    public function testDoesNotAddTheManagerLinkIfThereIsNoSystemNode(): void
    {
        $factory = new MenuFactory();
        $system = $factory->createItem('content');

        $menu = $factory->createItem('mainMenu');
        $menu->addChild($system);

        $event = new MenuEvent($factory, $menu);
        $security = $this->getSecurity();
        $router = $this->createMock(RouterInterface::class);
        $requestStack = new RequestStack();

        $listener = new BackendMenuListener($security, $router, $requestStack, false, 'contao-manager.phar.php', null);
        $listener($event);

        $this->assertNull($event->getTree()->getChild('system'));
    }

    private function getSecurity(bool $isAdmin = true): Security
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_ADMIN')
            ->willReturn($isAdmin)
        ;

        return $security;
    }
}
