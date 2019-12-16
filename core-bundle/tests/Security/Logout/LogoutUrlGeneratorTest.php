<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Security\Logout;

use Contao\CoreBundle\Security\Logout\LogoutUrlGenerator;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Firewall\SwitchUserListener;
use Symfony\Component\Security\Http\Logout\LogoutUrlGenerator as BaseLogoutUrlGenerator;

class LogoutUrlGeneratorTest extends TestCase
{
    public function testReturnsTheDefaultLogoutUrl(): void
    {
        $urlGenerator = $this->createMock(BaseLogoutUrlGenerator::class);
        $urlGenerator
            ->expects($this->once())
            ->method('getLogoutUrl')
            ->willReturn('/contao/logout')
        ;

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($this->createMock(UsernamePasswordToken::class))
        ;

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->never())
            ->method('generate')
        ;

        $generator = new LogoutUrlGenerator($urlGenerator, $security, $router);
        $generator->getLogoutUrl();
    }

    public function testReturnsTheSwitchUserExitUrl(): void
    {
        $urlGenerator = $this->createMock(BaseLogoutUrlGenerator::class);
        $urlGenerator
            ->expects($this->never())
            ->method('getLogoutUrl')
        ;

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($this->createMock(SwitchUserToken::class))
        ;

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with('contao_backend', ['do' => 'user', '_switch_user' => SwitchUserListener::EXIT_VALUE])
            ->willReturn('/contao?do=user&_switch_user=_exit')
        ;

        $generator = new LogoutUrlGenerator($urlGenerator, $security, $router);
        $generator->getLogoutUrl();
    }
}
