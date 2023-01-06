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
use Contao\CoreBundle\EventListener\Menu\BackendColorSchemeListener;
use Contao\TestCase\ContaoTestCase;
use Knp\Menu\MenuFactory;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class BackendColorSchemeListenerTest extends ContaoTestCase
{
    public function testAddsTheColorSchemeButton(): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_USER')
            ->willReturn(true)
        ;

        $factory = new MenuFactory();

        $menu = $factory->createItem('headerMenu');
        $menu->addChild($factory->createItem('submenu'));

        $event = new MenuEvent($factory, $menu);

        $listener = new BackendColorSchemeListener($security, $this->getTranslator());
        $listener($event);

        $children = $event->getTree()->getChild('submenu')->getChildren();

        $this->assertCount(1, $children);
        $this->assertSame(['color-scheme'], array_keys($children));

        $this->assertSame('color-scheme', $children['color-scheme']->getLabel());
        $this->assertSame('#', $children['color-scheme']->getUri());
        $this->assertSame(['class' => 'color-scheme'], $children['color-scheme']->getAttributes());

        $this->assertSame(
            [
                'safe_label' => true,
                'translation_domain' => false,
            ],
            $children['color-scheme']->getExtras()
        );

        $this->assertSame(
            [
                'class' => 'icon-color-scheme',
                'data-controller' => 'contao--color-scheme',
                'data-contao--color-scheme-target' => 'label',
                'data-contao--color-scheme-i18n-value' => '{"dark":"MSC.darkMode","light":"MSC.lightMode"}',
            ],
            $children['color-scheme']->getLinkAttributes()
        );
    }

    public function testDoesNotAddTheColorSchemeButtonIfTheUserRoleIsNotGranted(): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_USER')
            ->willReturn(false)
        ;

        $factory = new MenuFactory();

        $menu = $factory->createItem('headerMenu');
        $menu->addChild($factory->createItem('submenu'));

        $event = new MenuEvent($factory, $menu);

        $listener = new BackendColorSchemeListener($security, $this->getTranslator());
        $listener($event);

        $children = $event->getTree()->getChild('submenu')->getChildren();

        $this->assertCount(0, $children);
    }

    public function testDoesNotAddTheColorSchemeButtonIfTheNameDoesNotMatch(): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_USER')
            ->willReturn(true)
        ;

        $factory = new MenuFactory();

        $menu = $factory->createItem('mainMenu');
        $menu->addChild($factory->createItem('submenu'));

        $event = new MenuEvent($factory, $menu);

        $listener = new BackendColorSchemeListener($security, $this->getTranslator());
        $listener($event);

        $children = $event->getTree()->getChild('submenu')->getChildren();

        $this->assertCount(0, $children);
    }

    public function testDoesNotAddTheColorSchemeButtonIfThereIsNoSubmenu(): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_USER')
            ->willReturn(true)
        ;

        $factory = new MenuFactory();
        $menu = $factory->createItem('headerMenu');
        $event = new MenuEvent($factory, $menu);

        $listener = new BackendColorSchemeListener($security, $this->getTranslator());
        $listener($event);

        $children = $event->getTree()->getChildren();

        $this->assertCount(0, $children);
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
