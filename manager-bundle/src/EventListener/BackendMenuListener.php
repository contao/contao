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

use Contao\CoreBundle\Event\MenuEvent;
use Symfony\Component\Security\Core\Security;

final class BackendMenuListener
{
    /**
     * @var Security
     */
    private $security;

    /**
     * @var string
     */
    private $managerPath;

    public function __construct(Security $security, ?string $managerPath)
    {
        $this->security = $security;
        $this->managerPath = $managerPath;
    }

    /**
     * Adds a link to the Contao Manager in the back end navigation.
     */
    public function onBuild(MenuEvent $event): void
    {
        if (null === $this->managerPath || !$this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        $categoryNode = $event->getTree()->getChild('system');

        if (null === $categoryNode) {
            return;
        }

        $item = $event->getFactory()->createItem(
            'contao_manager',
            [
                'label' => 'Contao Manager',
                'attributes' => [
                    'title' => 'Contao Manager',
                    'href' => '/'.$this->managerPath,
                    'class' => 'navigation contao_manager',
                ],
            ]
        );

        $categoryNode->addChild($item);
    }
}
