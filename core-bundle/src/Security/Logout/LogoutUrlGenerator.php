<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Security\Logout;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Firewall\SwitchUserListener;
use Symfony\Component\Security\Http\Logout\LogoutUrlGenerator as BaseLogoutUrlGenerator;

class LogoutUrlGenerator
{
    /**
     * @var BaseLogoutUrlGenerator
     */
    private $urlGenerator;

    /**
     * @var Security
     */
    private $security;

    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(BaseLogoutUrlGenerator $urlGenerator, Security $security, RouterInterface $router)
    {
        $this->urlGenerator = $urlGenerator;
        $this->security = $security;
        $this->router = $router;
    }

    public function getLogoutUrl(): string
    {
        $token = $this->security->getToken();

        if (!$token instanceof SwitchUserToken) {
            return $this->urlGenerator->getLogoutUrl();
        }

        $params = ['do' => 'user', '_switch_user' => SwitchUserListener::EXIT_VALUE];

        return $this->router->generate('contao_backend', $params);
    }
}
