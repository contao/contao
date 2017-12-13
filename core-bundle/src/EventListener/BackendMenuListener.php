<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
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
                $categoryNode->setDisplayChildren(false !== strpos($categoryData['class'], 'node-expanded'));

                $tree->addChild($categoryNode);
            }

            // Create the child nodes
            foreach ($categoryData['modules'] as $moduleName => $moduleData) {
                $moduleNode = $this->createNode($factory, $moduleName, $moduleData);
                $moduleNode->setCurrent((bool) $moduleData['isActive']);
                $moduleNode->setAttribute('class', $categoryName);

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
        return $factory->createItem(
            $name,
            [
                'label' => $attributes['label'],
                'attributes' => [
                    'title' => $attributes['title'],
                    'href' => $attributes['href'],
                ],
            ]
        );
    }
}
