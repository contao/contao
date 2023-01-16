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

use Contao\CoreBundle\Event\MenuEvent;
use Contao\CoreBundle\Event\PreviewUrlCreateEvent;
use Contao\CoreBundle\EventListener\Menu\BackendPreviewListener;
use Contao\TestCase\ContaoTestCase;
use Knp\Menu\MenuFactory;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class BackendPreviewListenerTest extends ContaoTestCase
{
    /**
     * @dataProvider getPreviewData
     */
    public function testAddsThePreviewButton(string $do, int $id): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_USER')
            ->willReturn(true)
        ;

        $router = $this->createMock(RouterInterface::class);
        $router
            ->method('generate')
            ->with('contao_backend_preview')
            ->willReturn('/contao/preview')
        ;

        $request = new Request();
        $request->query->set('do', $do);
        $request->query->set('id', $id);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(
                function (PreviewUrlCreateEvent $e) use ($do, $id) {
                    $this->assertSame($do, $e->getKey());
                    $this->assertSame($id, $e->getId());

                    return true;
                }
            ))
        ;

        $factory = new MenuFactory();

        $menu = $factory->createItem('headerMenu');
        $menu->addChild($factory->createItem('submenu'));
        $menu->addChild($factory->createItem('burger'));

        $event = new MenuEvent($factory, $menu);

        $listener = new BackendPreviewListener(
            $security,
            $router,
            $requestStack,
            $this->getTranslator(),
            $eventDispatcher
        );

        $listener($event);

        $children = $event->getTree()->getChildren();

        $this->assertCount(3, $children);
        $this->assertSame(['preview', 'submenu', 'burger'], array_keys($children));

        $this->assertSame('MSC.fePreview', $children['preview']->getLabel());
        $this->assertSame(['translation_domain' => 'contao_default'], $children['preview']->getExtras());

        $this->assertSame(
            [
                'class' => 'icon-preview',
                'title' => 'MSC.fePreviewTitle',
                'target' => '_blank',
                'accesskey' => 'f',
            ],
            $children['preview']->getLinkAttributes()
        );
    }

    public function getPreviewData(): \Generator
    {
        yield ['', 0];
        yield ['page', 42];
        yield ['article', 3];
        yield ['news', 1];
    }

    public function testDoesNotAddThePreviewButtonIfTheUserRoleIsNotGranted(): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_USER')
            ->willReturn(false)
        ;

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->never())
            ->method('generate')
        ;

        $nodeFactory = new MenuFactory();
        $event = new MenuEvent($nodeFactory, $nodeFactory->createItem('headerMenu'));

        $listener = new BackendPreviewListener(
            $security,
            $router,
            new RequestStack(),
            $this->createMock(TranslatorInterface::class),
            $this->createMock(EventDispatcher::class)
        );

        $listener($event);

        $tree = $event->getTree();

        $this->assertCount(0, $tree->getChildren());
    }

    public function testDoesNotAddThePreviewButtonIfTheNameDoesNotMatch(): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_USER')
            ->willReturn(true)
        ;

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->never())
            ->method('generate')
        ;

        $nodeFactory = new MenuFactory();
        $event = new MenuEvent($nodeFactory, $nodeFactory->createItem('root'));

        $listener = new BackendPreviewListener(
            $security,
            $router,
            new RequestStack(),
            $this->createMock(TranslatorInterface::class),
            $this->createMock(EventDispatcher::class)
        );

        $listener($event);

        $tree = $event->getTree();

        $this->assertCount(0, $tree->getChildren());
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
