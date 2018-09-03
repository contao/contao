<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\EventListener;

use Contao\BackendUser;
use Contao\CoreBundle\Event\MenuEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class BackendMenuListener
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var string
     */
    private $managerPath;

    /**
     * BackendMenuListener constructor.
     *
     * @param TokenStorageInterface $tokenStorage
     * @param string|null           $managerPath
     */
    public function __construct(TokenStorageInterface $tokenStorage, ?string $managerPath)
    {
        $this->tokenStorage = $tokenStorage;
        $this->managerPath  = $managerPath;
    }

    /**
     * Adds the contao manager to the backend navigation.
     */
    public function onBuild(MenuEvent $event): void
    {
        if ($this->managerPath === null || !$this->isAdminUser()) {
            return;
        }

        $factory = $event->getFactory();
        $tree = $event->getTree();
        $categoryNode = $tree->getChild('system');

        if (null === $categoryNode) {
            return;
        }

        $item = $factory->createItem(
            'contao_manager',
            [
                'label' => 'Contao Manager',
                'attributes' => [
                    'title' => 'Contao Manager',
                    'href' => '/' . $this->managerPath,
                    'target' => '_blank',
                    'class' => 'navigation contao_manager'
                ],
            ]
        );

        $categoryNode->addChild($item);
    }

    private function isAdminUser(): bool
    {
        $token = $this->tokenStorage->getToken();

        if (null === $token) {
            return false;
        }

        $user = $token->getUser();

        if (!$user instanceof BackendUser) {
            return false;
        }

        return $user->isAdmin;
    }
}
