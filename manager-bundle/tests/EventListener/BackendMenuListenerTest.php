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
use Symfony\Contracts\Translation\TranslatorInterface;

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
        $requestStack = new RequestStack();
        $translator = $this->getTranslator();

        $listener = new BackendMenuListener($security, $router, $requestStack, $translator, false, null, null);
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

        $translator = $this->getTranslator();

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

        $listener = new BackendMenuListener($security, $router, $requestStack, $translator, false, null, $jwtManager);
        $listener($event);

        $children = $event->getTree()->getChildren();

        $this->assertCount(2, $children);
        $this->assertSame($expect, array_keys($children));

        $debug = $children['debug'];

        $this->assertSame('debug_mode', $debug->getLabel());
        $this->assertSame('/contao?do=debug&key=enable&referer=ZG89cGFnZQ==&ref=foo', $debug->getUri());
        $this->assertSame(['class' => 'icon-debug', 'title' => 'debug_mode'], $debug->getLinkAttributes());
        $this->assertSame(['translation_domain' => 'ContaoManagerBundle'], $debug->getExtras());
    }

    public function getItems(): \Generator
    {
        yield ['alerts', ['alerts', 'debug']];
        yield ['preview', ['debug', 'preview']];
    }

    public function testAddsTheHoverClassIfTheDebugModeIsEnabled(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $translator = $this->getTranslator();
        $router = $this->createMock(RouterInterface::class);

        $factory = new MenuFactory();
        $item = $factory->createItem('alerts');

        $menu = $factory->createItem('headerMenu');
        $menu->addChild($item);

        $event = new MenuEvent($factory, $menu);
        $jwtManager = $this->createMock(JwtManager::class);
        $security = $this->getSecurity();

        $listener = new BackendMenuListener($security, $router, $requestStack, $translator, true, null, $jwtManager);
        $listener($event);

        $children = $event->getTree()->getChildren();

        $this->assertSame(['class' => 'icon-debug hover', 'title' => 'debug_mode'], $children['debug']->getLinkAttributes());
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
        $translator = $this->getTranslator();

        $listener = new BackendMenuListener($security, $router, $requestStack, $translator, false, null, null);
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
        $translator = $this->getTranslator();
        $jwtManager = $this->createMock(JwtManager::class);

        $listener = new BackendMenuListener($security, $router, $requestStack, $translator, false, null, $jwtManager);
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
        $translator = $this->getTranslator();
        $jwtManager = $this->createMock(JwtManager::class);

        $listener = new BackendMenuListener($security, $router, $requestStack, $translator, false, null, $jwtManager);

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
        $translator = $this->getTranslator();
        $managerPath = 'contao-manager.phar.php';

        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('getUriForPath')
            ->with('/'.$managerPath)
            ->willReturnArgument(0)
        ;

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $listener = new BackendMenuListener($security, $router, $requestStack, $translator, false, $managerPath, null);
        $listener($event);

        $children = $event->getTree()->getChild('system')->getChildren();

        $this->assertCount(1, $children);
        $this->assertArrayHasKey('contao_manager', $children);

        $manager = $children['contao_manager'];

        $this->assertSame('Contao Manager', $manager->getLabel());
        $this->assertSame('/contao-manager.phar.php', $manager->getUri());
        $this->assertSame(['class' => 'navigation contao_manager', 'title' => 'contao_manager_title'], $manager->getLinkAttributes());
        $this->assertSame(['translation_domain' => false], $manager->getExtras());
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
        $translator = $this->getTranslator();

        $requestStack = new RequestStack();
        $requestStack->push($this->createMock(Request::class));

        $listener = new BackendMenuListener($security, $router, $requestStack, $translator, false, null, null);
        $listener($event);

        $this->assertCount(0, $event->getTree()->getChild('system')->getChildren());
    }

    public function testDoesNotAddTheManagerLinkIfTheRequestStackIsEmpty(): void
    {
        $factory = new MenuFactory();
        $system = $factory->createItem('system');

        $menu = $factory->createItem('mainMenu');
        $menu->addChild($system);

        $event = new MenuEvent($factory, $menu);
        $security = $this->getSecurity();
        $router = $this->createMock(RouterInterface::class);
        $requestStack = new RequestStack();
        $translator = $this->getTranslator();
        $managerPath = 'contao-manager.phar.php';

        $listener = new BackendMenuListener($security, $router, $requestStack, $translator, false, $managerPath, null);
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
        $translator = $this->getTranslator();
        $managerPath = 'contao-manager.phar.php';

        $listener = new BackendMenuListener($security, $router, $requestStack, $translator, false, $managerPath, null);
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

    private function getTranslator(): TranslatorInterface
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->willReturnCallback(static fn (string $id): string => $id)
        ;

        return $translator;
    }
}
