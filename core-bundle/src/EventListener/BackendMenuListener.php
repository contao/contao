<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener;

use Contao\BackendUser;
use Contao\CoreBundle\Event\MenuEvent;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;

/**
 * @internal
 */
class BackendMenuListener
{
    /**
     * @var Security
     */
    private $security;

    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(Security $security, RouterInterface $router)
    {
        $this->security = $security;
        $this->router = $router;
    }

    /**
     * Adds the back end user navigation.
     */
    public function __invoke(MenuEvent $event): void
    {
        $user = $this->security->getUser();

        if (!$user instanceof BackendUser) {
            return;
        }

        $factory = $event->getFactory();
        $tree = $event->getTree();
        $modules = $user->navigation();
        $path = $this->router->generate('contao_backend');

        foreach ($modules as $categoryName => $categoryData) {
            $categoryNode = $tree->getChild($categoryName);

            if (!$categoryNode) {
                $categoryNode = $factory
                    ->createItem($categoryName)
                    ->setLabel($categoryData['label'])
                    ->setUri($categoryData['href'])
                    ->setLinkAttribute('class', $this->getClassFromAttributes($categoryData))
                    ->setLinkAttribute('title', $categoryData['title'])
                    ->setLinkAttribute('onclick', "return AjaxRequest.toggleNavigation(this, '".$categoryName."', '".$path."')")
                    ->setChildrenAttribute('id', $categoryName)
                    ->setExtra('translation_domain', false)
                ;

                if (isset($categoryData['class']) && preg_match('/\bnode-collapsed\b/', $categoryData['class'])) {
                    $categoryNode->setAttribute('class', 'collapsed');
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
            $classes = array_flip(array_filter(explode(' ', $attributes['class'])));
            unset($classes['node-expanded'], $classes['node-collapsed'], $classes['trail']);
        }

        return implode(' ', array_keys($classes));
    }
}
