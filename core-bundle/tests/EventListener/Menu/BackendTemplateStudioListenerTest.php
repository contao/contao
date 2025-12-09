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

use Contao\CoreBundle\Controller\BackendTemplateStudioController;
use Contao\CoreBundle\Event\MenuEvent;
use Contao\CoreBundle\EventListener\Menu\BackendTemplateStudioListener;
use Contao\TestCase\ContaoTestCase;
use Knp\Menu\ItemInterface;
use Knp\Menu\MenuFactory;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class BackendTemplateStudioListenerTest extends ContaoTestCase
{
    public function testDoesNothingIfTheTemplateStudioIsNotEnabled(): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->never())
            ->method('isGranted')
        ;

        $router = $this->createMock(RouterInterface::class);
        $requestStack = new RequestStack();
        $translator = $this->getTranslator();
        $event = $this->createMock(MenuEvent::class);

        $listener = new BackendTemplateStudioListener($security, $router, $requestStack, $translator, false);
        $listener($event);
    }

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

        $listener = new BackendTemplateStudioListener($security, $router, $requestStack, $translator, true);
        $listener($event);
    }

    public function testDoesNothingIfTheMenuIsNotTheMainMenu(): void
    {
        $item = $this->createMock(ItemInterface::class);
        $item
            ->expects($this->once())
            ->method('getName')
            ->willReturn('headerMenu')
        ;

        $item
            ->expects($this->never())
            ->method('getChild')
        ;

        $event = $this->createMock(MenuEvent::class);
        $event
            ->expects($this->once())
            ->method('getTree')
            ->willReturn($item)
        ;

        $security = $this->getSecurity(true);
        $router = $this->createMock(RouterInterface::class);
        $requestStack = new RequestStack();
        $translator = $this->getTranslator();

        $listener = new BackendTemplateStudioListener($security, $router, $requestStack, $translator, true);
        $listener($event);
    }

    public function testDoesNothingIfTheCategoryIsNotDesign(): void
    {
        $item = $this->createMock(ItemInterface::class);
        $item
            ->expects($this->once())
            ->method('getName')
            ->willReturn('mainMenu')
        ;

        $item
            ->expects($this->once())
            ->method('getChild')
            ->willReturn(null)
        ;

        $event = $this->createMock(MenuEvent::class);
        $event
            ->expects($this->exactly(2))
            ->method('getTree')
            ->willReturn($item)
        ;

        $event
            ->expects($this->never())
            ->method('getFactory')
        ;

        $security = $this->getSecurity(true);
        $router = $this->createMock(RouterInterface::class);
        $requestStack = new RequestStack();
        $translator = $this->getTranslator();

        $listener = new BackendTemplateStudioListener($security, $router, $requestStack, $translator, true);
        $listener($event);
    }

    public function testDoesNothingIfThereIsNoRequest(): void
    {
        $item = $this->createMock(ItemInterface::class);
        $item
            ->expects($this->once())
            ->method('getName')
            ->willReturn('mainMenu')
        ;

        $item
            ->expects($this->once())
            ->method('getChild')
            ->willReturn((new MenuFactory())->createItem('design'))
        ;

        $event = $this->createMock(MenuEvent::class);
        $event
            ->expects($this->exactly(2))
            ->method('getTree')
            ->willReturn($item)
        ;

        $event
            ->expects($this->never())
            ->method('getFactory')
        ;

        $security = $this->getSecurity(true);
        $router = $this->createMock(RouterInterface::class);
        $requestStack = new RequestStack();
        $translator = $this->getTranslator();

        $listener = new BackendTemplateStudioListener($security, $router, $requestStack, $translator, true);
        $listener($event);
    }

    public function testAddsTheTemplateStudioMenuItemToTheMainMenu(): void
    {
        $nodeFactory = new MenuFactory();
        $design = $nodeFactory->createItem('design');

        $mainMenu = $nodeFactory->createItem('mainMenu');
        $mainMenu->addChild($design);

        $event = new MenuEvent($nodeFactory, $mainMenu);
        $security = $this->getSecurity(true);
        $router = $this->createMock(RouterInterface::class);
        $translator = $this->getTranslator();

        $request = new Request();
        $request->attributes->set('_controller', BackendTemplateStudioController::class);

        $requestStack = new RequestStack([$request]);

        $listener = new BackendTemplateStudioListener($security, $router, $requestStack, $translator, true);
        $listener($event);

        $children = $event->getTree()->getChildren()['design']->getChildren();

        $this->assertArrayHasKey('template-studio', $children);
        $this->assertSame('MOD.template_studio.0', $children['template-studio']->getLabel());

        $this->assertSame(
            [
                'class' => 'navigation template-studio',
                'title' => 'MOD.template_studio.1',
            ],
            $children['template-studio']->getLinkAttributes(),
        );

        $this->assertTrue($children['template-studio']->isCurrent());
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
