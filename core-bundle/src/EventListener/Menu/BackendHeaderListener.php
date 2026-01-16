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
use Contao\CoreBundle\String\HtmlAttributes;
use Contao\StringUtil;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

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
        private readonly TranslatorInterface $translator,
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

        $manualTitle = $this->translator->trans('MSC.manual', [], 'contao_default');

        $manual = $factory
            ->createItem('manual')
            ->setLabel($manualTitle)
            ->setUri('https://to.contao.org/manual')
            ->setLinkAttribute('class', 'icon-manual')
            ->setLinkAttribute('title', $manualTitle)
            ->setLinkAttribute('target', '_blank')
            ->setExtra('safe_label', true)
            ->setExtra('translation_domain', false)
        ;

        $tree->addChild($manual);

        $alerts = $event->getFactory()
            ->createItem('alerts')
            ->setLabel($this->getAlertsLabel())
            ->setExtra('safe_label', true)
            ->setExtra('translation_domain', false)
        ;

        $tree->addChild($alerts);

        $submenu = $factory
            ->createItem('submenu')
            ->setLabel('<button type="button" title="'.$this->translator->trans('MSC.showProfile', [], 'contao_default').'" data-contao--toggle-state-target="controller" data-action="contao--toggle-state#toggle:prevent">'.$user->username.'</button>')
            ->setAttribute('class', 'submenu')
            ->setAttribute('data-controller', 'contao--toggle-state')
            ->setAttribute('data-action', 'click@document->contao--toggle-state#documentClick keydown.esc@document->contao--toggle-state#close')
            ->setAttribute('data-contao--toggle-state-active-class', 'active')
            ->setAttribute('data-contao--toggle-state-active-title-value', $this->translator->trans('MSC.hideProfile', [], 'contao_default'))
            ->setAttribute('data-contao--toggle-state-inactive-title-value', $this->translator->trans('MSC.showProfile', [], 'contao_default'))
            ->setExtra('safe_label', true)
            ->setLabelAttribute('class', 'profile')
            ->setExtra('translation_domain', false)
            ->setChildrenAttribute('data-contao--toggle-state-target', 'controls')
        ;

        $tree->addChild($submenu);

        $info = $factory
            ->createItem('info')
            ->setLabel(\sprintf('<strong>%s</strong> %s', $user->name, $user->email))
            ->setAttribute('class', 'info')
            ->setExtra('safe_label', true)
            ->setExtra('translation_domain', false)
        ;

        $submenu->addChild($info);

        $login = $factory
            ->createItem('login')
            ->setAttribute('class', 'separator')
            ->setLabel('MSC.profile')
            ->setUri($this->router->generate('contao_backend', ['do' => 'login', 'act' => 'edit', 'id' => $user->id, 'nb' => '1']))
            ->setLinkAttribute('class', 'icon-profile')
            ->setExtra('translation_domain', 'contao_default')
        ;

        $submenu->addChild($login);

        $security = $factory
            ->createItem('security')
            ->setLabel('MSC.security')
            ->setUri($this->router->generate('contao_backend', ['do' => 'security']))
            ->setLinkAttribute('class', 'icon-security')
            ->setExtra('translation_domain', 'contao_default')
        ;

        $submenu->addChild($security);

        $favorites = $factory
            ->createItem('favorites')
            ->setLabel('MSC.favorites')
            ->setUri($this->router->generate('contao_backend', ['do' => 'favorites']))
            ->setLinkAttribute('class', 'icon-favorites')
            ->setExtra('translation_domain', 'contao_default')
        ;

        $submenu->addChild($favorites);

        $colorScheme = $factory
            ->createItem('color-scheme')
            ->setLabel('<button class="icon-color-scheme" type="button" data-contao--color-scheme-target="label" data-action="contao--color-scheme#toggle:prevent">'.$this->translator->trans('MSC.lightMode', [], 'contao_default').'</button>')
            ->setAttribute('class', 'separator')
            ->setAttribute('data-controller', 'contao--color-scheme')
            ->setAttribute(
                'data-contao--color-scheme-i18n-value',
                json_encode(
                    [
                        'dark' => $this->translator->trans('MSC.darkMode', [], 'contao_default'),
                        'light' => $this->translator->trans('MSC.lightMode', [], 'contao_default'),
                    ],
                    JSON_THROW_ON_ERROR,
                ),
            )
            ->setLabelAttribute('class', 'color-scheme')
            ->setExtra('safe_label', true)
            ->setExtra('translation_domain', false)
        ;

        $submenu->addChild($colorScheme);

        $burgerAttributes = (new HtmlAttributes())
            ->set('id', 'burger')
            ->set('type', 'button')
            ->set('title', $this->translator->trans('MSC.showMainNavigation', [], 'contao_default'))
            ->set('data-controller', 'contao--toggle-handler')
            ->set('data-action', 'contao--toggle-handler#toggle:prevent')
            ->set('data-contao--toggle-handler-active-title-value', $this->translator->trans('MSC.hideMainNavigation', [], 'contao_default'))
            ->set('data-contao--toggle-handler-inactive-title-value', $this->translator->trans('MSC.showMainNavigation', [], 'contao_default'))
            ->set('data-contao--toggle-handler-contao--toggle-receiver-outlet', '#left')
        ;

        $burger = $factory
            ->createItem('burger')
            ->setLabel(\sprintf('<button%s><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h18M3 6h18M3 18h18"/></svg></button>', (string) $burgerAttributes))
            ->setAttribute('class', 'burger')
            ->setExtra('safe_label', true)
            ->setExtra('translation_domain', false)
        ;

        $tree->addChild($burger);
    }

    private function getAlertsLabel(): string
    {
        $systemMessages = $this->translator->trans('MSC.systemMessages', [], 'contao_default');

        $label = \sprintf(
            '<a href="%s" class="icon-alert" title="%s" data-turbo-prefetch="false" onclick="Backend.openModalIframe({\'title\':\'%s\',\'url\':this.href});return false">%s</a>',
            $this->router->generate('contao_backend_alerts'),
            htmlspecialchars($systemMessages, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5),
            StringUtil::specialchars(str_replace("'", "\\'", $systemMessages)),
            $systemMessages,
        );

        $adapter = $this->framework->getAdapter(Backend::class);
        $count = substr_count($adapter->getSystemMessages(), 'class="tl_error');

        if ($count > 0) {
            $label .= '<sup>'.$count.'</sup>';
        }

        return $label;
    }
}
