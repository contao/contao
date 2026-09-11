<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\Menu;

use Contao\Backend;
use Contao\BackendUser;
use Contao\CoreBundle\Event\MenuEvent;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Menu\BackendMenuBuilder;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Routing\RouterInterface;

/**
 * Make sure this listener comes before the other ones adding to its tree.
 *
 * @internal
 */
#[AsEventListener(priority: 10)]
class BackendHeaderListener
{
    public function __construct(
        private readonly Security $security,
        private readonly RouterInterface $router,
        private readonly ContaoFramework $framework,
    ) {
    }

    public function __invoke(MenuEvent $event): void
    {
        $user = $this->security->getUser();

        if (!$user instanceof BackendUser) {
            return;
        }

        $name = $event->getTree()->getName();

        if ('headerMenu' !== $name) {
            return;
        }

        $factory = $event->getFactory();
        $tree = $event->getTree();

        $tree->addChild($this->createManual($factory));
        $tree->addChild($this->createAlerts($factory));
        $tree->addChild($this->createProfileMenu($factory, $user));
        $tree->addChild($this->createNavigationToggle($factory));
    }

    private function createManual(FactoryInterface $factory): ItemInterface
    {
        return $factory
            ->createItem('manual')
            ->setLabel('MSC.manual')
            ->setUri('https://to.contao.org/manual')
            ->setLinkAttribute('target', '_blank')
            ->setExtra(BackendMenuBuilder::EXTRA_ICON, 'manual.svg')
            ->setExtra('safe_label', true)
            ->setExtra('translation_domain', 'contao_default')
        ;
    }

    private function createAlerts(FactoryInterface $factory): ItemInterface
    {
        return $factory
            ->createItem('alerts')
            ->setLabel('MSC.systemMessages')
            ->setUri($this->router->generate('contao_backend_alerts'))
            ->setExtra(BackendMenuBuilder::EXTRA_CONTENT_TEMPLATE, '@Contao/backend/menu/_alerts.html.twig')
            ->setExtra('alerts_count', $this->getAlertsCount())
            ->setExtra('translation_domain', 'contao_default')
        ;
    }

    private function createProfileMenu(FactoryInterface $factory, BackendUser $user): ItemInterface
    {
        $submenu = $factory
            ->createItem('submenu')
            ->setLabel($user->username)
            ->setAttribute('class', 'submenu')
            ->setLabelAttribute('class', 'profile')
            ->setExtra(BackendMenuBuilder::EXTRA_CONTENT_TEMPLATE, '@Contao/backend/menu/item/_profile.html.twig')
            ->setExtra('translation_domain', false)
        ;

        $info = $factory
            ->createItem('info')
            ->setLabel($user->name)
            ->setAttribute('class', 'info')
            ->setExtra(BackendMenuBuilder::EXTRA_CONTENT_TEMPLATE, '@Contao/backend/menu/item/_info.html.twig')
            ->setExtra('detail', $user->email)
            ->setExtra('translation_domain', false)
        ;

        $submenu->addChild($info);
        $this->addProfileLinks($factory, $submenu, $user);

        $colorScheme = $factory
            ->createItem('color-scheme')
            ->setLabel('MSC.lightMode')
            ->setLabelAttribute('class', 'color-scheme')
            ->setExtra(BackendMenuBuilder::EXTRA_CONTENT_TEMPLATE, '@Contao/backend/menu/item/_color_scheme.html.twig')
            ->setExtra(BackendMenuBuilder::EXTRA_HAS_DIVIDER, true)
            ->setExtra('translation_domain', 'contao_default')
        ;

        $submenu->addChild($colorScheme);

        return $submenu;
    }

    private function addProfileLinks(FactoryInterface $factory, ItemInterface $submenu, BackendUser $user): void
    {
        $login = $factory
            ->createItem('login')
            ->setLabel('MSC.profile')
            ->setUri($this->router->generate('contao_backend', ['do' => 'login', 'act' => 'edit', 'id' => $user->id, 'nb' => '1']))
            ->setExtra(BackendMenuBuilder::EXTRA_ICON, 'profile_small.svg')
            ->setExtra(BackendMenuBuilder::EXTRA_HAS_DIVIDER, true)
            ->setExtra('translation_domain', 'contao_default')
        ;

        $submenu->addChild($login);

        $security = $factory
            ->createItem('security')
            ->setLabel('MSC.security')
            ->setUri($this->router->generate('contao_backend', ['do' => 'security']))
            ->setExtra(BackendMenuBuilder::EXTRA_ICON, 'shield_small.svg')
            ->setExtra('translation_domain', 'contao_default')
        ;

        $submenu->addChild($security);

        $favorites = $factory
            ->createItem('favorites')
            ->setLabel('MSC.favorites')
            ->setUri($this->router->generate('contao_backend', ['do' => 'favorites']))
            ->setExtra(BackendMenuBuilder::EXTRA_ICON, 'favorites_small.svg')
            ->setExtra('translation_domain', 'contao_default')
        ;

        $submenu->addChild($favorites);
    }

    private function createNavigationToggle(FactoryInterface $factory): ItemInterface
    {
        return $factory
            ->createItem('burger')
            ->setLabel('MSC.showMainNavigation')
            ->setAttribute('class', 'burger')
            ->setExtra(BackendMenuBuilder::EXTRA_CONTENT_TEMPLATE, '@Contao/backend/menu/item/_navigation_toggle.html.twig')
            ->setExtra('translation_domain', 'contao_default')
        ;
    }

    private function getAlertsCount(): int
    {
        $adapter = $this->framework->getAdapter(Backend::class);

        return substr_count($adapter->getSystemMessages(), 'class="tl_error');
    }
}
