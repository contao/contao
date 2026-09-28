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

use Contao\CoreBundle\Event\MenuEvent;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\System;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Make sure this listener comes before the other ones adding to its tree.
 *
 * @internal
 */
#[AsEventListener(priority: 10)]
class BackendMainListener
{
    public function __construct(
        private readonly Security $security,
        private readonly RequestStack $requestStack,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly TranslatorBagInterface&TranslatorInterface $translator,
    ) {
    }

    public function __invoke(MenuEvent $event): void
    {
        $name = $event->getTree()->getName();

        if ('mainMenu' !== $name) {
            return;
        }

        $factory = $event->getFactory();
        $tree = $event->getTree();
        $request = $this->requestStack->getCurrentRequest();
        $modules = $this->getBackendModules();
        $collapsed = $this->getCollapsedNodes();

        foreach ($modules as $categoryName => $categoryData) {
            $categoryNode = $tree->getChild($categoryName);

            if (!$categoryNode) {
                $categoryNode = $factory
                    ->createItem($categoryName)
                    ->setLabel($categoryData['label'])
                    ->setUri($categoryData['href'])
                    ->setLinkAttribute('class', $categoryData['class'])
                    ->setLinkAttribute('title', $this->translator->trans('MSC.collapseNode', [], 'contao_default'))
                    ->setLinkAttribute('data-action', 'contao--toggle-navigation#toggle:prevent')
                    ->setLinkAttribute('data-contao--toggle-navigation-category-param', $categoryName)
                    ->setLinkAttribute('data-contao--tooltips-target', 'tooltip')
                    ->setLinkAttribute('aria-controls', $categoryName)
                    ->setLinkAttribute('data-turbo-prefetch', 'false')
                    ->setChildrenAttribute('id', $categoryName)
                    ->setExtra('translation_domain', false)
                ;

                if ($collapsed[$categoryName] ?? false) {
                    $categoryNode->setLinkAttribute('title', $this->translator->trans('MSC.expandNode', [], 'contao_default'));
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
                    ->setLinkAttribute('class', $nodeData['class'])
                    ->setLinkAttribute('title', $nodeData['title'])
                    ->setLinkAttribute('data-contao--tooltips-target', 'tooltip')
                    ->setExtra('translation_domain', false)
                ;

                if ($request?->query->get('do') === $nodeName) {
                    $categoryNode->setLinkAttribute('class', $categoryNode->getLinkAttribute('class').' trail');
                    $moduleNode->setCurrent(true);
                }

                $categoryNode->addChild($moduleNode);
            }
        }
    }

    /**
     * Creates the legacy data structure for the "getUserNavigation" hook
     * (backwards compatibility).
     */
    private function getBackendModules(): array
    {
        $modules = [];
        $request = $this->requestStack->getCurrentRequest();

        foreach ($GLOBALS['BE_MOD'] as $groupName => $groupModules) {
            if (!empty($groupModules)) {
                $modules[$groupName]['class'] = 'group-'.$groupName;
                $modules[$groupName]['title'] = $this->translator->trans('MSC.collapseNode', [], 'contao_default');
                $modules[$groupName]['label'] = $this->translateModule($groupName);
                $modules[$groupName]['href'] = $this->urlGenerator->generate('contao_backend', ['do' => $request?->query->get('do'), 'mtg' => $groupName]);
                $modules[$groupName]['ajaxUrl'] = $this->urlGenerator->generate('contao_backend');

                foreach ($groupModules as $moduleName => $moduleConfig) {
                    $hasAccess = (isset($moduleConfig['disablePermissionChecks']) && true === $moduleConfig['disablePermissionChecks']) || $this->security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_MODULE, $moduleName);
                    $isHidden = isset($moduleConfig['hideInNavigation']) && true === $moduleConfig['hideInNavigation'];

                    if ($hasAccess && !$isHidden) {
                        $modules[$groupName]['modules'][$moduleName] = $moduleConfig;
                        $modules[$groupName]['modules'][$moduleName]['title'] = $this->translator->getCatalogue()->has("MOD.$moduleName.1", 'contao_default') ? $this->translator->trans("MOD.$moduleName.1", [], 'contao_default') : '';
                        $modules[$groupName]['modules'][$moduleName]['label'] = $this->translateModule($moduleName);
                        $modules[$groupName]['modules'][$moduleName]['class'] = 'navigation '.$moduleName;
                        $modules[$groupName]['modules'][$moduleName]['href'] = $this->urlGenerator->generate('contao_backend', ['do' => $moduleName]);
                    }
                }

                // Unset the group if there are no allowed modules
                if (empty($modules[$groupName]['modules'])) {
                    unset($modules[$groupName]);
                }
            }
        }

        // HOOK: add custom logic
        if (isset($GLOBALS['TL_HOOKS']['getUserNavigation']) && \is_array($GLOBALS['TL_HOOKS']['getUserNavigation'])) {
            trigger_deprecation('contao/core-bundle', '6.0', 'The "getUserNavigation" hook is deprecated and will no longer work in Contao 7. Use the "%s" event instead', MenuEvent::class);

            foreach ($GLOBALS['TL_HOOKS']['getUserNavigation'] as $callback) {
                $modules = System::importStatic($callback[0])->{$callback[1]}($modules, true);
            }
        }

        return $modules;
    }

    private function getCollapsedNodes(): array
    {
        $sessionBag = $this->requestStack->getSession()->getBag('contao_backend');

        if (!$sessionBag instanceof AttributeBagInterface) {
            return [];
        }

        return array_map(
            static fn ($v) => !$v,
            (array) $sessionBag->get('backend_modules'),
        );
    }

    private function translateModule(string $name): string
    {
        if ($this->translator->getCatalogue()->has("MOD.$name.0", 'contao_default')) {
            return $this->translator->trans("MOD.$name.0", [], 'contao_default');
        }

        if ($this->translator->getCatalogue()->has("MOD.$name", 'contao_default')) {
            return $this->translator->trans("MOD.$name", [], 'contao_default');
        }

        return $name;
    }
}
