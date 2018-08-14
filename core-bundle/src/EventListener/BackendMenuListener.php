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
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class BackendMenuListener
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Adds the back end user navigation.
     *
     * @param MenuEvent $event
     */
    public function onBuild(MenuEvent $event): void
    {
        $token = $this->tokenStorage->getToken();

        if (null === $token || !($user = $token->getUser()) instanceof BackendUser) {
            return;
        }

        $factory = $event->getFactory();
        $tree = $event->getTree();
        $modules = $user->navigation();

        foreach ($modules as $categoryName => $categoryData) {
            $categoryNode = $tree->getChild($categoryName);

            if (!$categoryNode) {
                $categoryNode = $this->createNode($factory, $categoryName, $categoryData);

                if (isset($categoryData['class']) && preg_match('/\bnode-collapsed\b/', $categoryData['class'])) {
                    $categoryNode->setDisplayChildren(false);
                }

                $tree->addChild($categoryNode);
            }

            // Create the child nodes
            foreach ($categoryData['modules'] as $moduleName => $moduleData) {
                $moduleNode = $this->createNode($factory, $moduleName, $moduleData);
                $moduleNode->setCurrent((bool) $moduleData['isActive']);

                $categoryNode->addChild($moduleNode);
            }
        }
    }

    /**
     * Creates a node.
     *
     * @param FactoryInterface $factory
     * @param string           $name
     * @param array            $attributes
     *
     * @return ItemInterface
     */
    private function createNode(FactoryInterface $factory, string $name, array $attributes): ItemInterface
    {
        $classes = [];

        // Remove the default CSS classes and keep potentially existing custom ones (see #1357)
        if (isset($attributes['class'])) {
            $classes = array_flip(array_filter(explode(' ', $attributes['class'])));
            unset($classes['node-expanded'], $classes['node-collapsed'], $classes['trail']);
        }

        return $factory->createItem(
            $name,
            [
                'label' => $attributes['label'],
                'attributes' => [
                    'title' => $attributes['title'],
                    'href' => $attributes['href'],
                    'class' => implode(' ', array_keys($classes)),
                ],
            ]
        );
    }
}
