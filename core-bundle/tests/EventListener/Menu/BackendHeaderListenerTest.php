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

use Contao\Backend;
use Contao\BackendUser;
use Contao\CoreBundle\Event\MenuEvent;
use Contao\CoreBundle\EventListener\Menu\BackendHeaderListener;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Tests\TestCase;
use Knp\Menu\MenuFactory;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class BackendHeaderListenerTest extends TestCase
{
    public function testBuildsTheHeaderMenu(): void
    {
        $user = $this->mockClassWithProperties(BackendUser::class);
        $user->id = 1;
        $user->name = 'Foo Bar';
        $user->username = 'foo';
        $user->email = 'foo@bar.com';

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $router = $this->createMock(RouterInterface::class);
        $router
            ->method('generate')
            ->willReturnCallback(
                static function (string $name, array $options = []): string {
                    if ('contao_backend_alerts' === $name) {
                        return '/contao/alerts';
                    }

                    return '/contao?'.http_build_query($options);
                },
            )
        ;

        $request = Request::create('https://localhost/contao?do=pages&ref=123456');
        $request->attributes->set('_contao_referer_id', 'bar');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $systemMessages = $this->mockAdapter(['getSystemMessages']);
        $systemMessages
            ->expects($this->once())
            ->method('getSystemMessages')
            ->willReturn('<p class="tl_error">Foo</p>')
        ;

        $nodeFactory = new MenuFactory();
        $event = new MenuEvent($nodeFactory, $nodeFactory->createItem('headerMenu'));

        $listener = new BackendHeaderListener(
            $security,
            $router,
            $requestStack,
            $this->getTranslator(),
            $this->mockContaoFramework([Backend::class => $systemMessages]),
        );

        $listener($event);

        $tree = $event->getTree();

        $this->assertSame('headerMenu', $tree->getName());

        $children = $tree->getChildren();

        $this->assertSame(['manual', 'jobs', 'alerts', 'color-scheme', 'submenu', 'burger'], array_keys($children));

        // Manual
        $this->assertSame('MSC.manual', $children['manual']->getLabel());
        $this->assertSame('https://to.contao.org/manual', $children['manual']->getUri());
        $this->assertSame(['safe_label' => true, 'translation_domain' => false], $children['alerts']->getExtras());

        $this->assertSame(
            [
                'class' => 'icon-manual',
                'title' => 'MSC.manual',
                'target' => '_blank',
            ],
            $children['manual']->getLinkAttributes(),
        );

        // Alerts
        $this->assertSame('<a href="/contao/alerts" class="icon-alert" title="MSC.systemMessages" data-turbo-prefetch="false" onclick="Backend.openModalIframe({\'title\':\'MSC.systemMessages\',\'url\':this.href});return false">MSC.systemMessages</a><sup>1</sup>', $children['alerts']->getLabel());
        $this->assertSame(['safe_label' => true, 'translation_domain' => false], $children['alerts']->getExtras());

        // Color scheme
        $this->assertSame('color-scheme', $children['color-scheme']->getLabel());
        $this->assertSame('#', $children['color-scheme']->getUri());
        $this->assertSame(['safe_label' => true, 'translation_domain' => false], $children['color-scheme']->getExtras());

        $this->assertSame(
            [
                'class' => 'icon-color-scheme',
                'title' => '',
                'data-controller' => 'contao--color-scheme',
                'data-contao--color-scheme-target' => 'label',
                'data-contao--color-scheme-i18n-value' => '{"dark":"MSC.darkMode","light":"MSC.lightMode"}',
            ],
            $children['color-scheme']->getLinkAttributes(),
        );

        // Submenu
        $this->assertSame('<button type="button" data-contao--profile-target="button" data-action="contao--profile#toggle">MSC.user foo</button>', $children['submenu']->getLabel());
        $this->assertSame(['class' => 'submenu', 'data-controller' => 'contao--profile', 'data-contao--profile-target' => 'menu', 'data-action' => 'click@document->contao--profile#documentClick'], $children['submenu']->getAttributes());
        $this->assertSame(['class' => 'profile'], $children['submenu']->getLabelAttributes());
        $this->assertSame(['safe_label' => true, 'translation_domain' => false], $children['submenu']->getExtras());

        $grandChildren = $children['submenu']->getChildren();

        $this->assertCount(4, $grandChildren);
        $this->assertSame(['info', 'login', 'security', 'favorites'], array_keys($grandChildren));

        // Info
        $this->assertSame('<strong>Foo Bar</strong> foo@bar.com', $grandChildren['info']->getLabel());
        $this->assertSame(['class' => 'info'], $grandChildren['info']->getAttributes());
        $this->assertSame(['safe_label' => true, 'translation_domain' => false], $grandChildren['info']->getExtras());

        // Login
        $this->assertSame('MSC.profile', $grandChildren['login']->getLabel());
        $this->assertSame('/contao?do=login&act=edit&id=1&ref=bar', $grandChildren['login']->getUri());
        $this->assertSame(['class' => 'icon-profile'], $grandChildren['login']->getLinkAttributes());
        $this->assertSame(['translation_domain' => 'contao_default'], $grandChildren['login']->getExtras());

        // Security
        $this->assertSame('MSC.security', $grandChildren['security']->getLabel());
        $this->assertSame('/contao?do=security&ref=bar', $grandChildren['security']->getUri());
        $this->assertSame(['class' => 'icon-security'], $grandChildren['security']->getLinkAttributes());
        $this->assertSame(['translation_domain' => 'contao_default'], $grandChildren['security']->getExtras());

        // Favorites
        $this->assertSame('MSC.favorites', $grandChildren['favorites']->getLabel());
        $this->assertSame('/contao?do=favorites&ref=bar', $grandChildren['favorites']->getUri());
        $this->assertSame(['class' => 'icon-favorites'], $grandChildren['favorites']->getLinkAttributes());
        $this->assertSame(['translation_domain' => 'contao_default'], $grandChildren['favorites']->getExtras());

        // Burger
        $this->assertSame('<button type="button" id="burger"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h18M3 6h18M3 18h18"/></svg></button>', $children['burger']->getLabel());
        $this->assertSame(['class' => 'burger'], $children['burger']->getAttributes());
        $this->assertSame(['safe_label' => true, 'translation_domain' => false], $children['burger']->getExtras());
    }

    public function testDoesNotBuildTheHeaderMenuIfNoUserIsGiven(): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->method('getUser')
            ->willReturn(null)
        ;

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->never())
            ->method('generate')
        ;

        $nodeFactory = new MenuFactory();
        $event = new MenuEvent($nodeFactory, $nodeFactory->createItem('headerMenu'));

        $listener = new BackendHeaderListener(
            $security,
            $router,
            new RequestStack(),
            $this->createMock(TranslatorInterface::class),
            $this->createMock(ContaoFramework::class),
        );

        $listener($event);

        $tree = $event->getTree();

        $this->assertCount(0, $tree->getChildren());
    }

    public function testDoesNotBuildTheHeaderMenuIfTheNameDoesNotMatch(): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->method('getUser')
            ->willReturn(null)
        ;

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->never())
            ->method('generate')
        ;

        $nodeFactory = new MenuFactory();
        $event = new MenuEvent($nodeFactory, $nodeFactory->createItem('root'));

        $listener = new BackendHeaderListener(
            $security,
            $router,
            new RequestStack(),
            $this->createMock(TranslatorInterface::class),
            $this->createMock(ContaoFramework::class),
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
