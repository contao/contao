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
    private $webDir;

    /**
     * @var string
     */
    private $managerPath;

    public function __construct(TokenStorageInterface $tokenStorage, string $webDir, string $managerPath)
    {
        $this->tokenStorage = $tokenStorage;
        $this->webDir = $webDir;
        $this->managerPath = $managerPath;
    }

    /**
     * Adds a link to the Contao Manager to the back end navigation.
     */
    public function onBuild(MenuEvent $event): void
    {
        if (!$this->hasManager() || !$this->isAdminUser()) {
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
                    'href' => '/'.$this->managerPath,
                    'target' => '_blank',
                    'class' => 'navigation contao_manager',
                ],
            ]
        );

        $categoryNode->addChild($item);
    }

    private function hasManager(): bool
    {
        return $this->managerPath && file_exists($this->webDir.'/'.$this->managerPath);
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
