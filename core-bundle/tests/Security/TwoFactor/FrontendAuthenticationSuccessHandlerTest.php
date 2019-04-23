<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Security\TwoFactor;

use Contao\CoreBundle\Security\TwoFactor\FrontendAuthenticationSuccessHandler;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendUser;
use Contao\User;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\HttpUtils;

class FrontendAuthenticationSuccessHandlerTest extends TestCase
{
    public function testRedirectsOnAuthenticationSuccess(): void
    {
        /** @var User|MockObject $user */
        $user = $this->createPartialMock(FrontendUser::class, ['save']);
        $user
            ->expects($this->once())
            ->method('save')
        ;

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $utils = $this->createMock(HttpUtils::class);
        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->once())
            ->method('remove')
            ->with(Security::AUTHENTICATION_ERROR)
        ;

        $request = new Request();
        $request->request->set('_target_path', 'http://localhost');

        $request->setSession($session);

        $utils
            ->expects($this->once())
            ->method('createRedirectResponse')
            ->willReturn(new RedirectResponse('http://localhost'))
        ;

        $handler = new FrontendAuthenticationSuccessHandler($utils);
        $handler->onAuthenticationSuccess($request, $token);
    }
}
