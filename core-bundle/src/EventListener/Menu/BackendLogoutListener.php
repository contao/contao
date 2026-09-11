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
use Contao\CoreBundle\Menu\BackendMenuBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Http\Firewall\SwitchUserListener;
use Symfony\Component\Security\Http\Logout\LogoutUrlGenerator as BaseLogoutUrlGenerator;

/**
 * @internal
 */
#[AsEventListener(priority: -96)]
class BackendLogoutListener
{
    public function __construct(
        private readonly Security $security,
        private readonly RouterInterface $router,
        private readonly BaseLogoutUrlGenerator $urlGenerator,
    ) {
    }

    public function __invoke(MenuEvent $event): void
    {
        if (!$this->security->isGranted('ROLE_USER')) {
            return;
        }

        $tree = $event->getTree();

        if ('headerMenu' !== $tree->getName() || !$submenu = $tree->getChild('submenu')) {
            return;
        }

        $token = $this->security->getToken();
        $isSwitched = $token instanceof SwitchUserToken;

        $logout = $event
            ->getFactory()
            ->createItem('logout')
            ->setLabel($isSwitched ? 'MSC.switchBT' : 'MSC.logoutBT')
            ->setUri($isSwitched ? $this->getLogoutUrl() : $this->urlGenerator->getLogoutUrl())
            ->setLinkAttribute('accesskey', 'q')
            ->setLinkAttribute('data-turbo-prefetch', 'false')
            ->setExtra(BackendMenuBuilder::EXTRA_ICON, 'exit.svg')
            ->setExtra(BackendMenuBuilder::EXTRA_HAS_DIVIDER, true)
            ->setExtra('translation_params', $isSwitched ? [$token->getOriginalToken()->getUserIdentifier()] : [])
            ->setExtra('translation_domain', 'contao_default')
        ;

        $submenu->addChild($logout);
    }

    private function getLogoutUrl(): string
    {
        $params = ['do' => 'user', '_switch_user' => SwitchUserListener::EXIT_VALUE];

        return $this->router->generate('contao_backend', $params);
    }
}
