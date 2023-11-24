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
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Http\Firewall\SwitchUserListener;
use Symfony\Component\Security\Http\Logout\LogoutUrlGenerator as BaseLogoutUrlGenerator;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
class BackendLogoutListener
{
    public function __construct(
        private readonly Security $security,
        private readonly RouterInterface $router,
        private readonly BaseLogoutUrlGenerator $urlGenerator,
        private readonly TranslatorInterface $translator,
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

        $logout = $event
            ->getFactory()
            ->createItem('logout')
            ->setLabel($this->getLogoutLabel())
            ->setUri($this->getLogoutUrl())
            ->setAttribute('class', 'logout')
            ->setLinkAttribute('class', 'icon-logout')
            ->setLinkAttribute('accesskey', 'q')
            ->setExtra('translation_domain', false)
        ;

        $submenu->addChild($logout);
    }

    private function getLogoutLabel(): string
    {
        $token = $this->security->getToken();

        if ($token instanceof SwitchUserToken) {
            return $this->translator->trans(
                'MSC.switchBT',
                [$token->getOriginalToken()->getUserIdentifier()],
                'contao_default',
            );
        }

        return $this->translator->trans('MSC.logoutBT', [], 'contao_default');
    }

    private function getLogoutUrl(): string
    {
        $token = $this->security->getToken();

        if (!$token instanceof SwitchUserToken) {
            return $this->urlGenerator->getLogoutUrl();
        }

        $params = ['do' => 'user', '_switch_user' => SwitchUserListener::EXIT_VALUE];

        return $this->router->generate('contao_backend', $params);
    }
}
