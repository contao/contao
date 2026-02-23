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
use Contao\CoreBundle\EventListener\Menu\BackendLoginListener;
use Contao\TestCase\ContaoTestCase;
use Knp\Menu\MenuFactory;
use Symfony\Contracts\Translation\TranslatorInterface;

class BackendLoginListenerTest extends ContaoTestCase
{
    public function testAddsThePasskeyButton(): void
    {
        $factory = new MenuFactory();
        $menu = $factory->createItem('loginMenu');

        $event = new MenuEvent($factory, $menu);

        $listener = new BackendLoginListener($this->getTranslator());
        $listener($event);

        $children = $event->getTree()->getChildren();

        $this->assertCount(1, $children);
        $this->assertSame(['passkey'], array_keys($children));

        $this->assertSame(
            '<button type="button" class="tl_submit has-icon" data-action="contao--webauthn#signin">MSC.passkeyLogin</button>',
            $children['passkey']->getLabel(),
        );

        $this->assertSame(['safe_label' => true, 'translation_domain' => false], $children['passkey']->getExtras());
        $this->assertSame(['class' => 'passkey'], $children['passkey']->getAttributes());
    }

    private function getTranslator(): TranslatorInterface
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->willReturnCallback(static fn (string $id): string => $id)
        ;

        return $translator;
    }
}
