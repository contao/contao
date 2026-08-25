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
use Contao\CoreBundle\Menu\BackendMenuBuilder;
use Contao\TestCase\ContaoTestCase;
use Knp\Menu\Matcher\Matcher;
use Knp\Menu\MenuFactory;
use Knp\Menu\Renderer\TwigRenderer;
use Knp\Menu\Twig\MenuExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

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

        $this->assertSame('MSC.passkeyLogin', $children['passkey']->getLabel());

        $this->assertSame([BackendMenuBuilder::EXTRA_CONTENT_TEMPLATE => '@Contao/backend/menu/item/_passkey.html.twig', 'translation_domain' => false], $children['passkey']->getExtras());
        $this->assertSame(['class' => 'passkey'], $children['passkey']->getAttributes());

        $html = $this->createRenderer()->render($menu);
        $this->assertStringContainsString('<li class="passkey first last">', $html);
        $this->assertStringContainsString('<button type="button" class="tl_submit has-icon" data-action="contao--webauthn-authentication#authenticate">MSC.passkeyLogin</button>', $html);
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

    private function createRenderer(): TwigRenderer
    {
        $loader = new FilesystemLoader();
        $loader->addPath(__DIR__.'/../../../contao/templates', 'Contao');
        $loader->addPath(__DIR__.'/../../../../vendor/knplabs/knp-menu-bundle/templates', 'KnpMenu');
        $loader->addPath(__DIR__.'/../../../../vendor/knplabs/knp-menu/src/Knp/Menu/Resources/views');

        $twig = new Environment($loader);
        $twig->addExtension(new MenuExtension());
        $twig->addExtension(new TranslationExtension($this->getTranslator()));

        return new TwigRenderer($twig, '@Contao/backend/chrome/_login_providers.html.twig', new Matcher());
    }
}
