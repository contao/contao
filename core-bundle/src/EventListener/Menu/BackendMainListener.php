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
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;
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
        $tree = $event->getTree();
        $name = $tree->getName();

        if ('mainMenu' !== $name) {
            return;
        }

        $factory = $event->getFactory();
        $modules = $this->getBackendModules();

        foreach ($modules as $categoryName => $categoryData) {
            $categoryNode = $tree->getChild($categoryName);

            if (!$categoryNode) {
                $categoryNode = $this->createCategory($factory, $categoryName, $categoryData);
                $tree->addChild($categoryNode);
            }

            $this->addModules($factory, $categoryNode, $categoryData['modules']);
        }
    }

    private function createCategory(FactoryInterface $factory, string $name, array $data): ItemInterface
    {
        $node = $factory
            ->createItem($name)
            ->setLabel($data['label'])
            ->setUri($data['href'])
            ->setExtra('translation_domain', false)
        ;

        if ($class = $this->getCustomClass($data, ['group-'.$name])) {
            $node->setLinkAttribute('class', $class);
        }

        return $node;
    }

    private function addModules(FactoryInterface $factory, ItemInterface $category, array $modules): void
    {
        // Create the child nodes
        foreach ($modules as $name => $data) {
            $node = $factory
                ->createItem($name)
                ->setLabel($data['label'])
                ->setUri($data['href'])
                ->setCurrent((bool) $data['isActive'])
                ->setExtra('title', $data['title'])
                ->setExtra('translation_domain', false)
            ;

            if ($class = $this->getCustomClass($data, ['navigation', $name])) {
                $node->setLinkAttribute('class', $class);
            }

            $category->addChild($node);
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
                        $modules[$groupName]['modules'][$moduleName]['isActive'] = false;
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

        // Mark the active module and its group
        $currentModule = $request?->query->get('do');

        foreach ($modules as $groupName => $groupData) {
            foreach ($groupData['modules'] ?? [] as $moduleName => $moduleData) {
                if ($currentModule === $moduleName) {
                    $modules[$groupName]['class'] .= ' trail';
                    $modules[$groupName]['modules'][$moduleName]['isActive'] = true;
                }
            }
        }

        return $modules;
    }

    private function getCustomClass(array $attributes, array $defaultClasses): string
    {
        $classes = [];

        // Remove the default CSS classes and keep potentially existing custom ones (see #1357)
        if (isset($attributes['class'])) {
            $classes = array_flip(array_filter(explode(' ', (string) $attributes['class'])));

            foreach (['trail', ...$defaultClasses] as $class) {
                unset($classes[$class]);
            }
        }

        return implode(' ', array_keys($classes));
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
