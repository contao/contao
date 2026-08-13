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
use Symfony\Component\HttpFoundation\Request;
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
        private readonly TranslatorInterface&TranslatorBagInterface $translator,
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
        $modules = $this->getBackendModules($request);
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
     * We have to keep this logic from BackendUser::navigation() until the
     * "getUserNavigation" hook is removed.
     */
    private function getBackendModules(Request|null $request): array
    {
        $arrModules = [];

        foreach ($GLOBALS['BE_MOD'] as $strGroupName => $arrGroupModules) {
            if (!empty($arrGroupModules) && ('system' === $strGroupName || $this->security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_MODULE, array_keys($arrGroupModules)))) {
                $arrModules[$strGroupName]['class'] = 'group-'.$strGroupName;
                $arrModules[$strGroupName]['title'] = $this->translator->trans('MSC.collapseNode', [], 'contao_default');
                $arrModules[$strGroupName]['label'] = $this->translateModule($strGroupName);
                $arrModules[$strGroupName]['href'] = $this->urlGenerator->generate('contao_backend', ['do' => $request?->query->get('do'), 'mtg' => $strGroupName]);
                $arrModules[$strGroupName]['ajaxUrl'] = $this->urlGenerator->generate('contao_backend');

                foreach ($arrGroupModules as $strModuleName => $arrModuleConfig) {
                    // Check access
                    $blnAccess = (isset($arrModuleConfig['disablePermissionChecks']) && true === $arrModuleConfig['disablePermissionChecks']) || $this->security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_MODULE, $strModuleName);
                    $blnHide = isset($arrModuleConfig['hideInNavigation']) && true === $arrModuleConfig['hideInNavigation'];

                    if ($blnAccess && !$blnHide) {
                        $arrModules[$strGroupName]['modules'][$strModuleName] = $arrModuleConfig;
                        $arrModules[$strGroupName]['modules'][$strModuleName]['title'] = $this->translator->getCatalogue()->has("MOD.$strModuleName.1", 'contao_default') ? $this->translator->trans("MOD.$strModuleName.1", [], 'contao_default') : '';
                        $arrModules[$strGroupName]['modules'][$strModuleName]['label'] = $this->translateModule($strModuleName);
                        $arrModules[$strGroupName]['modules'][$strModuleName]['class'] = 'navigation '.$strModuleName;
                        $arrModules[$strGroupName]['modules'][$strModuleName]['href'] = $this->urlGenerator->generate('contao_backend', ['do' => $strModuleName]);
                    }
                }
            }
        }

        // HOOK: add custom logic
        if (isset($GLOBALS['TL_HOOKS']['getUserNavigation']) && \is_array($GLOBALS['TL_HOOKS']['getUserNavigation'])) {
            trigger_deprecation('contao/core-bundle', '6.1', 'The "getUserNavigation" hook is deprecated and will be removed in Contao 7. Use the "%s" event instead', MenuEvent::class);

            foreach ($GLOBALS['TL_HOOKS']['getUserNavigation'] as $callback) {
                $arrModules = System::importStatic($callback[0])->{$callback[1]}($arrModules, true);
            }
        }

        return $arrModules;
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
