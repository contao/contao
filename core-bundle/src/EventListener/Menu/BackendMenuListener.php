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
use Contao\StringUtil;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
class BackendMenuListener
{
    public function __construct(
        private readonly Security $security,
        private readonly RouterInterface $router,
        private readonly RequestStack $requestStack,
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

        if ('mainMenu' === $name) {
            $this->buildMainMenu($event, $user);
        } elseif ('headerMenu' === $name) {
            $this->buildHeaderMenu($event, $user);
        }
    }

    private function buildMainMenu(MenuEvent $event, BackendUser $user): void
    {
        $factory = $event->getFactory();
        $tree = $event->getTree();
        $modules = $user->navigation();

        foreach ($modules as $categoryName => $categoryData) {
            $categoryNode = $tree->getChild($categoryName);

            if (!$categoryNode) {
                $categoryNode = $factory
                    ->createItem($categoryName)
                    ->setLabel($categoryData['label'])
                    ->setUri($categoryData['href'])
                    ->setLinkAttribute('class', $this->getClassFromAttributes($categoryData))
                    ->setLinkAttribute('title', $categoryData['title'])
                    ->setLinkAttribute('data-action', 'contao--toggle-navigation#toggle:prevent')
                    ->setLinkAttribute('data-contao--toggle-navigation-category-param', $categoryName)
                    ->setLinkAttribute('aria-controls', $categoryName)
                    ->setChildrenAttribute('id', $categoryName)
                    ->setExtra('translation_domain', false)
                ;

                if (isset($categoryData['class']) && preg_match('/\bnode-collapsed\b/', (string) $categoryData['class'])) {
                    $categoryNode->setAttribute('class', 'collapsed');
                    $categoryNode->setLinkAttribute('aria-expanded', 'false');
                } else {
                    $categoryNode->setLinkAttribute('aria-expanded', 'true');
                }

                $tree->addChild($categoryNode);
            }

            // Create the child nodes
            foreach ($categoryData['modules'] as $nodeName => $nodeData) {
                $moduleNode = $factory
                    ->createItem($nodeName)
                    ->setLabel($nodeData['label'])
                    ->setUri($nodeData['href'])
                    ->setLinkAttribute('class', $this->getClassFromAttributes($nodeData))
                    ->setLinkAttribute('title', $nodeData['title'])
                    ->setCurrent((bool) $nodeData['isActive'])
                    ->setExtra('translation_domain', false)
                ;

                $categoryNode->addChild($moduleNode);
            }
        }
    }

    private function buildHeaderMenu(MenuEvent $event, BackendUser $user): void
    {
        $factory = $event->getFactory();
        $tree = $event->getTree();
        $ref = $this->getRefererId();

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

        $colorScheme = $event
            ->getFactory()
            ->createItem('color-scheme')
            ->setUri('#')
            ->setLinkAttribute('class', 'icon-color-scheme')
            ->setLinkAttribute('title', '') // Required for the tips.js script
            ->setLinkAttribute('data-controller', 'contao--color-scheme')
            ->setLinkAttribute('data-contao--color-scheme-target', 'label')
            ->setLinkAttribute(
                'data-contao--color-scheme-i18n-value',
                json_encode(
                    [
                        'dark' => $this->translator->trans('MSC.darkMode', [], 'contao_default'),
                        'light' => $this->translator->trans('MSC.lightMode', [], 'contao_default'),
                    ],
                    JSON_THROW_ON_ERROR,
                ),
            )
            ->setExtra('safe_label', true)
            ->setExtra('translation_domain', false)
        ;

        $tree->addChild($colorScheme);

        $submenu = $factory
            ->createItem('submenu')
            ->setLabel('<button type="button">'.$this->translator->trans('MSC.user', [], 'contao_default').' '.$user->username.'</button>')
            ->setAttribute('class', 'submenu')
            ->setExtra('safe_label', true)
            ->setLabelAttribute('class', 'profile')
            ->setExtra('translation_domain', false)
        ;

        $tree->addChild($submenu);

        $info = $factory
            ->createItem('info')
            ->setLabel(sprintf('<strong>%s</strong> %s', $user->name, $user->email))
            ->setAttribute('class', 'info')
            ->setExtra('safe_label', true)
            ->setExtra('translation_domain', false)
        ;

        $submenu->addChild($info);

        $login = $factory
            ->createItem('login')
            ->setLabel('MSC.profile')
            ->setUri($this->router->generate('contao_backend', ['do' => 'login', 'ref' => $ref]))
            ->setLinkAttribute('class', 'icon-profile')
            ->setExtra('translation_domain', 'contao_default')
        ;

        $submenu->addChild($login);

        $security = $factory
            ->createItem('security')
            ->setLabel('MSC.security')
            ->setUri($this->router->generate('contao_backend', ['do' => 'security', 'ref' => $ref]))
            ->setLinkAttribute('class', 'icon-security')
            ->setExtra('translation_domain', 'contao_default')
        ;

        $submenu->addChild($security);

        $favorites = $factory
            ->createItem('favorites')
            ->setLabel('MSC.favorites')
            ->setUri($this->router->generate('contao_backend', ['do' => 'favorites', 'ref' => $ref]))
            ->setLinkAttribute('class', 'icon-favorites')
            ->setExtra('translation_domain', 'contao_default')
        ;

        $submenu->addChild($favorites);

        $buger = $factory
            ->createItem('burger')
            ->setLabel('<button type="button" id="burger"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h18M3 6h18M3 18h18"/></svg></button>')
            ->setAttribute('class', 'burger')
            ->setExtra('safe_label', true)
            ->setExtra('translation_domain', false)
        ;

        $tree->addChild($buger);
    }

    private function getAlertsLabel(): string
    {
        $systemMessages = $this->translator->trans('MSC.systemMessages', [], 'contao_default');

        $label = sprintf(
            '<a href="%s" class="icon-alert" title="%s" onclick="Backend.openModalIframe({\'title\':\'%s\',\'url\':this.href});return false">%s</a>',
            $this->router->generate('contao_backend_alerts'),
            htmlspecialchars($systemMessages),
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

    private function getRefererId(): string
    {
        if (!$request = $this->requestStack->getCurrentRequest()) {
            throw new \RuntimeException('The request stack did not contain a request');
        }

        return $request->attributes->get('_contao_referer_id');
    }

    private function getClassFromAttributes(array $attributes): string
    {
        $classes = [];

        // Remove the default CSS classes and keep potentially existing custom ones (see #1357)
        if (isset($attributes['class'])) {
            $classes = array_flip(array_filter(explode(' ', (string) $attributes['class'])));
            unset($classes['node-expanded'], $classes['node-collapsed'], $classes['trail']);
        }

        return implode(' ', array_keys($classes));
    }
}
