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

use Contao\BackendUser;
use Contao\CoreBundle\Event\MenuEvent;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Make sure this listener comes before the other ones adding to its tree.
 *
 * @internal
 */
#[AsEventListener(priority: 10)]
class BackendMainListener
{
    public function __construct(private readonly Security $security)
    {
    }

    public function __invoke(MenuEvent $event): void
    {
        $user = $this->security->getUser();

        if (!$user instanceof BackendUser) {
            return;
        }

        $name = $event->getTree()->getName();

        if ('mainMenu' !== $name) {
            return;
        }

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
                    ->setLinkAttribute('data-turbo-prefetch', 'false')
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
