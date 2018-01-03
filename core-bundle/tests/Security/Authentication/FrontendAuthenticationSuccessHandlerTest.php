<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Security\Authentication;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\Security\Authentication\FrontendAuthenticationSuccessHandler;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendUser;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\HttpUtils;
use Symfony\Component\Translation\TranslatorInterface;

class FrontendAuthenticationSuccessHandlerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $handler = $this->mockSuccessHandler();

        $this->assertInstanceOf(
            'Contao\CoreBundle\Security\Authentication\FrontendAuthenticationSuccessHandler',
            $handler
        );
    }

    public function testUsesTheDefaultUrlIfNotAFrontendUser(): void
    {
        $framework = $this->mockContaoFramework();

        $framework
            ->expects($this->never())
            ->method('getAdapter')
        ;

        $token = $this->createMock(TokenInterface::class);

        $token
            ->method('getUser')
            ->willReturn($this->createMock(UserInterface::class))
        ;

        $handler = $this->mockSuccessHandler($framework);
        $handler->onAuthenticationSuccess(new Request(), $token);
    }

    public function testUsesTheFixedTargetPath(): void
    {
        $framework = $this->mockContaoFramework();

        $framework
            ->expects($this->never())
            ->method('getAdapter')
        ;

        $request = new Request();
        $request->request->set('_always_use_target_path', '1');
        $request->request->set('_target_path', 'http://localhost/target');

        /** @var FrontendUser|\PHPUnit_Framework_MockObject_MockObject $user */
        $user = $this->createPartialMock(FrontendUser::class, ['save']);
        $user->username = 'foobar';
        $user->lastLogin = time() - 3600;
        $user->currentLogin = time() - 1800;
        $user->language = '';

        $user
            ->expects($this->once())
            ->method('save')
        ;

        $token = $this->createMock(TokenInterface::class);

        $token
            ->method('getUser')
            ->willReturn($user)
        ;

        $handler = $this->mockSuccessHandler($framework);
        $response = $handler->onAuthenticationSuccess($request, $token);

        $this->assertSame('http://localhost/target', $response->getTargetUrl());
    }

    public function testUsesTheUrlOfThePage(): void
    {
        $model = $this->createMock(PageModel::class);

        $model
            ->expects($this->once())
            ->method('getAbsoluteUrl')
            ->willReturn('http://localhost/page')
        ;

        $adapter = $this->mockAdapter(['findFirstActiveByMemberGroups']);

        $adapter
            ->expects($this->once())
            ->method('findFirstActiveByMemberGroups')
            ->with([2, 3])
            ->willReturn($model)
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $adapter]);

        /** @var FrontendUser|\PHPUnit_Framework_MockObject_MockObject $user */
        $user = $this->createPartialMock(FrontendUser::class, ['save']);
        $user->username = 'foobar';
        $user->lastLogin = time() - 3600;
        $user->currentLogin = time() - 1800;
        $user->language = '';
        $user->groups = serialize([2, 3]);

        $user
            ->expects($this->once())
            ->method('save')
        ;

        $token = $this->createMock(TokenInterface::class);

        $token
            ->method('getUser')
            ->willReturn($user)
        ;

        $handler = $this->mockSuccessHandler($framework);
        $response = $handler->onAuthenticationSuccess(new Request(), $token);

        $this->assertSame('http://localhost/page', $response->getTargetUrl());
    }

    public function testUsesTheDefaultUrlIfNotAPageModel(): void
    {
        $adapter = $this->mockAdapter(['findFirstActiveByMemberGroups']);

        $adapter
            ->expects($this->once())
            ->method('findFirstActiveByMemberGroups')
            ->with([2, 3])
            ->willReturn(null)
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $adapter]);

        $request = new Request();
        $request->attributes->set('_target_path', 'http://localhost/target');

        /** @var FrontendUser|\PHPUnit_Framework_MockObject_MockObject $user */
        $user = $this->createPartialMock(FrontendUser::class, ['save']);
        $user->username = 'foobar';
        $user->lastLogin = time() - 3600;
        $user->currentLogin = time() - 1800;
        $user->language = '';
        $user->groups = serialize([2, 3]);

        $user
            ->expects($this->once())
            ->method('save')
        ;

        $token = $this->createMock(TokenInterface::class);

        $token
            ->method('getUser')
            ->willReturn($user)
        ;

        $handler = $this->mockSuccessHandler($framework);
        $response = $handler->onAuthenticationSuccess($request, $token);

        $this->assertSame('http://localhost/target', $response->getTargetUrl());
    }

    /**
     * Mocks an authentication success handler.
     *
     * @param ContaoFrameworkInterface|null $framework
     *
     * @return FrontendAuthenticationSuccessHandler
     */
    private function mockSuccessHandler(ContaoFrameworkInterface $framework = null): FrontendAuthenticationSuccessHandler
    {
        if (null === $framework) {
            $framework = $this->mockContaoFramework();
        }

        return new FrontendAuthenticationSuccessHandler(new HttpUtils(), $framework);
    }
}
