<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Controller\ContentElement;

use Contao\BackendUser;
use Contao\ContentModel;
use Contao\CoreBundle\Cache\CacheTagManager;
use Contao\CoreBundle\Controller\ContentElement\LoginController;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Routing\PageFinder;
use Contao\CoreBundle\Security\TwoFactor\BackupCodeManager;
use Contao\CoreBundle\Security\TwoFactor\TrustedDeviceManager;
use Contao\FragmentTemplate;
use Contao\FrontendUser;
use Contao\PageModel;
use Contao\System;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Logout\LogoutUrlGenerator;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class LoginControllerTest extends ContentElementTestCase
{
    public function testReturnsIfTheUserIsNotAFrontendUser(): void
    {
        $response = $this->renderWithModelData(
            new LoginController(
                $this->mockSecurity(false),
                $this->createMock(UriSigner::class),
                $this->createMock(LogoutUrlGenerator::class),
                $this->createMock(AuthenticationUtils::class),
                $this->createMock(TranslatorInterface::class),
                $this->createMock(ContentUrlGenerator::class),
                $this->createMock(EventDispatcherInterface::class),
                $this->createMock(ContaoFramework::class),
            ),
            [
                'type' => 'login',
            ],
        );

        $this->assertSame('', $response->getContent());
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testShowsLogoutFormIfFrontendUserIsLoggedIn(): void
    {
        $response = $this->renderWithModelData(
            new LoginController(
                $this->mockSecurity(true, false, false),
                $this->createMock(UriSigner::class),
                $this->createMock(LogoutUrlGenerator::class),
                $this->createMock(AuthenticationUtils::class),
                $this->createMock(TranslatorInterface::class),
                $this->createMock(ContentUrlGenerator::class),
                $this->createMock(EventDispatcherInterface::class),
                $this->createMock(ContaoFramework::class),
            ),
            [
                'type' => 'login',
            ],
        );

        $this->assertSame('', $response->getContent());
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    private function mockSecurity(bool|null $frontendUser = null, bool|null $isRemembered = null, bool|null $twoFaInProgress = null, TokenInterface|null|bool $token = false): Security&MockObject
    {
        $user = null !== $frontendUser ? $this->createMock($frontendUser ? FrontendUser::class : BackendUser::class) :  null;

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $isGrantedMap = [];

        if (null !== $isRemembered) {
            $isGrantedMap[] = ['IS_REMEMBERED', null, $isRemembered];
        }

        if (null !== $twoFaInProgress) {
            $isGrantedMap[] = ['IS_AUTHENTICATED_2FA_IN_PROGRESS', null, $twoFaInProgress];
        }

        if ($isGrantedMap) {
            $security
                ->expects($this->exactly(count($isGrantedMap)))
                ->method('isGranted')
                ->willReturnMap($isGrantedMap)
            ;
        }

        if (false !== $token) {
            $security
                ->expects($this->once())
                ->method('getToken')
                ->willReturn($token)
            ;
        }

        return $security;
    }
}
