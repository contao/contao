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

use Contao\ArticleModel;
use Contao\CoreBundle\Event\MenuEvent;
use Contao\CoreBundle\Event\PreviewUrlCreateEvent;
use Contao\CoreBundle\EventListener\Menu\BackendPreviewListener;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\TestCase\ContaoTestCase;
use Knp\Menu\MenuFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class BackendPreviewListenerTest extends ContaoTestCase
{
    /**
     * @dataProvider getPreviewData
     */
    public function testAddsThePreviewButton(?string $do, string $uri, bool $dispatch): void
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
        $request->query->set('id', '42');

        if (null !== $do) {
            $request->query->set('do', $do);
        }

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $factory = new MenuFactory();
        $event = new MenuEvent($factory, $factory->createItem('headerMenu'));

        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $eventDispatcher
            ->expects($dispatch ? $this->once() : $this->never())
            ->method('dispatch')
            ->with($this->callback(
                function (PreviewUrlCreateEvent $e) {
                    $e->setQuery('news=42');

                    $this->assertSame('news', $e->getKey());
                    $this->assertSame(42, $e->getId());

                    return true;
                }
            ))
        ;

        /** @var ArticleModel&MockObject $article */
        $article = $this->mockClassWithProperties(ArticleModel::class);
        $article->pid = 3;

        $adapter = $this->mockAdapter(['findByPk']);
        $adapter
            ->expects('article' === $do ? $this->once() : $this->never())
            ->method('findByPk')
            ->with(42)
            ->willReturn($article)
        ;

        $listener = new BackendPreviewListener(
            $security,
            $router,
            $requestStack,
            $this->getTranslator(),
            $eventDispatcher,
            $this->mockContaoFramework([ArticleModel::class => $adapter])
        );

        $listener($event);

        $children = $event->getTree()->getChildren();

        $this->assertCount(1, $children);
        $this->assertSame(['preview'], array_keys($children));

        $this->assertSame('MSC.fePreview', $children['preview']->getLabel());
        $this->assertSame($uri, $children['preview']->getUri());
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
        yield [null, '/contao/preview', false];
        yield ['page', '/contao/preview?page=42', false];
        yield ['article', '/contao/preview?page=3', false];
        yield ['news', '/contao/preview?news=42', true];
    }

    /**
     * @dataProvider getItemNames
     */
    public function testAddsThePreviewButtonAfterTheAlertsButton(string $itemName, array $expect): void
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
        $request->query->set('do', 'page');
        $request->query->set('table', 'tl_page');

        $session = $this->createMock(Session::class);
        $session
            ->expects($this->once())
            ->method('get')
            ->with('CURRENT_ID')
            ->willReturn(3)
        ;

        $request->setSession($session);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $factory = new MenuFactory();

        $menu = $factory->createItem('headerMenu');
        $menu->addChild($factory->createItem($itemName));

        $event = new MenuEvent($factory, $menu);

        $listener = new BackendPreviewListener(
            $security,
            $router,
            $requestStack,
            $this->getTranslator(),
            $this->createMock(EventDispatcher::class),
            $this->mockContaoFramework()
        );

        $listener($event);

        $children = $event->getTree()->getChildren();

        $this->assertCount(2, $children);
        $this->assertSame($expect, array_keys($children));

        $this->assertSame('/contao/preview?page=3', $children['preview']->getUri());
    }

    public function getItemNames(): \Generator
    {
        yield ['alerts', ['alerts', 'preview']];
        yield ['debug', ['preview', 'debug']];
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
            $this->createMock(EventDispatcher::class),
            $this->createMock(ContaoFramework::class)
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
            $this->createMock(EventDispatcher::class),
            $this->createMock(ContaoFramework::class)
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
            ->willReturnCallback(
                static function (string $id): string {
                    return $id;
                }
            )
        ;

        return $translator;
    }
}
