<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener;

use Contao\CoreBundle\EventListener\TwoFactorFrontendListener;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\FrontendUser;
use Contao\PageModel;
use Contao\TestCase\ContaoTestCase;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorToken;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class TwoFactorFrontendListenerTest extends ContaoTestCase
{
    public function testReturnsIfRequestIsNotAFrontendRequest(): void
    {
        $request = $this->mockRequest();
        $event = $this->mockGetResponseEvent($request);
        $framework = $this->mockContaoFramework();
        $scopeMatcher = $this->mockScopeMatcher(false, $event);
        $tokenStorage = $this->mockTokenStorageWithToken();

        $listener = new TwoFactorFrontendListener($framework, $scopeMatcher, $tokenStorage);
        $listener->onKernelRequest($event);
    }

    public function testReturnsIfTokenIsNotATwoFactorToken(): void
    {
        $request = $this->mockRequest();
        $event = $this->mockGetResponseEvent($request);
        $framework = $this->mockContaoFramework();
        $scopeMatcher = $this->mockScopeMatcher(true, $event);
        $tokenStorage = $this->mockTokenStorageWithToken();

        $listener = new TwoFactorFrontendListener($framework, $scopeMatcher, $tokenStorage);
        $listener->onKernelRequest($event);
    }

    public function testReturnsIfRequestHasNoPageModel(): void
    {
        /** @var TokenInterface $token */
        $token = $this->mockToken(TwoFactorToken::class);
        $request = $this->mockRequest();
        $framework = $this->mockContaoFramework();
        $event = $this->mockGetResponseEvent($request);
        $scopeMatcher = $this->mockScopeMatcher(true, $event);
        $tokenStorage = $this->mockTokenStorageWithToken($token);

        $listener = new TwoFactorFrontendListener($framework, $scopeMatcher, $tokenStorage);
        $listener->onKernelRequest($event);
    }

    public function testReturnsIfUserIsNotAFrontendUser(): void
    {
        /** @var TokenInterface $token */
        $token = $this->mockToken(TwoFactorToken::class);
        $request = $this->mockRequest(true);
        $event = $this->mockGetResponseEvent($request);
        $framework = $this->mockContaoFramework();
        $scopeMatcher = $this->mockScopeMatcher(true, $event);
        $tokenStorage = $this->mockTokenStorageWithToken($token);

        $listener = new TwoFactorFrontendListener($framework, $scopeMatcher, $tokenStorage);
        $listener->onKernelRequest($event);
    }

    public function testThrowsPageNotFoundExceptionIfNoTwoFactorPageIsFound(): void
    {
        /** @var FrontendUser $user */
        $user = $this->mockClassWithProperties(FrontendUser::class, ['useTwoFactor' => false]);

        /** @var PageModel $pageModel */
        $pageModel = $this->mockClassWithProperties(PageModel::class, [
            'enforceTwoFactor' => true,
            'twoFactorJumpTo' => null,
        ]);

        $adapter = $this->mockAdapter(['findPublishedById']);
        $adapter
            ->expects($this->once())
            ->method('findPublishedById')
            ->willReturn(null)
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $adapter]);

        /** @var TokenInterface $token */
        $token = $this->mockToken(TwoFactorToken::class, true, $user);
        $request = $this->mockRequest(true, $pageModel);
        $event = $this->mockGetResponseEvent($request);
        $scopeMatcher = $this->mockScopeMatcher(true, $event);
        $tokenStorage = $this->mockTokenStorageWithToken($token);

        $this->expectException(PageNotFoundException::class);
        $this->expectExceptionMessage('No two-factor authentication page found.');

        $listener = new TwoFactorFrontendListener($framework, $scopeMatcher, $tokenStorage);
        $listener->onKernelRequest($event);
    }

    public function testReturnsIfTwoFactorIsEnforcedAndActualPageIsTwoFactorPage(): void
    {
        /** @var FrontendUser $user */
        $user = $this->mockClassWithProperties(FrontendUser::class, ['useTwoFactor' => false]);

        /** @var PageModel $pageModel */
        $pageModel = $this->mockClassWithProperties(PageModel::class, [
            'enforceTwoFactor' => true,
            'twoFactorJumpTo' => null,
            'id' => 1,
        ]);

        $adapter = $this->mockAdapter(['findPublishedById']);
        $adapter
            ->expects($this->once())
            ->method('findPublishedById')
            ->willReturn($pageModel)
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $adapter]);

        /** @var TokenInterface $token */
        $token = $this->mockToken(TwoFactorToken::class, true, $user);
        $request = $this->mockRequest(true, $pageModel);
        $event = $this->mockGetResponseEvent($request);
        $scopeMatcher = $this->mockScopeMatcher(true, $event);
        $tokenStorage = $this->mockTokenStorageWithToken($token);

        $listener = new TwoFactorFrontendListener($framework, $scopeMatcher, $tokenStorage);
        $listener->onKernelRequest($event);
    }

    public function testSetsEventResponseIfTwoFactorIsEnforcedAndTwoFactorPageFound(): void
    {
        /** @var FrontendUser $user */
        $user = $this->mockClassWithProperties(FrontendUser::class, ['useTwoFactor' => false]);

        /** @var PageModel $pageModel */
        $pageModel = $this->mockClassWithProperties(PageModel::class, [
            'enforceTwoFactor' => true,
            'twoFactorJumpTo' => 1,
            'id' => 1,
        ]);

        $response = new RedirectResponse('http://localhost/two_factor');

        $twoFactorPageModel = $this->mockClassWithProperties(PageModel::class, ['id' => 2]);
        $twoFactorPageModel
            ->expects($this->once())
            ->method('getAbsoluteUrl')
            ->willReturn('http://localhost/two_factor')
        ;

        $adapter = $this->mockAdapter(['findPublishedById']);
        $adapter
            ->expects($this->once())
            ->method('findPublishedById')
            ->willReturn($twoFactorPageModel)
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $adapter]);

        /** @var TokenInterface $token */
        $token = $this->mockToken(TwoFactorToken::class, true, $user);
        $request = $this->mockRequest(true, $pageModel);
        $event = $this->mockGetResponseEvent($request, $response);
        $scopeMatcher = $this->mockScopeMatcher(true, $event);
        $tokenStorage = $this->mockTokenStorageWithToken($token);

        $listener = new TwoFactorFrontendListener($framework, $scopeMatcher, $tokenStorage);
        $listener->onKernelRequest($event);

        $this->assertInstanceOf(RedirectResponse::class, $event->getResponse());
    }

    public function testReturnsIfUserHasTwoFactorEnabledAndTokenIsNotATwoFactorToken(): void
    {
        /** @var FrontendUser $user */
        $user = $this->mockClassWithProperties(FrontendUser::class, ['useTwoFactor' => true]);

        /** @var PageModel $pageModel */
        $pageModel = $this->mockClassWithProperties(PageModel::class, [
            'enforceTwoFactor' => false,
        ]);

        $adapter = $this->mockAdapter([]);
        $framework = $this->mockContaoFramework([PageModel::class => $adapter]);

        /** @var TokenInterface $token */
        $token = $this->mockToken(UsernamePasswordToken::class, true, $user);
        $request = $this->mockRequest(true, $pageModel);
        $event = $this->mockGetResponseEvent($request);
        $scopeMatcher = $this->mockScopeMatcher(true, $event);
        $tokenStorage = $this->mockTokenStorageWithToken($token);

        $listener = new TwoFactorFrontendListener($framework, $scopeMatcher, $tokenStorage);
        $listener->onKernelRequest($event);
    }

    public function testReturnsINoUnauthorizedPageIsFound(): void
    {
        /** @var FrontendUser $user */
        $user = $this->mockClassWithProperties(FrontendUser::class, ['useTwoFactor' => true]);

        /** @var PageModel $pageModel */
        $pageModel = $this->mockClassWithProperties(PageModel::class, [
            'enforceTwoFactor' => false,
            'rootId' => 0,
        ]);

        $adapter = $this->mockAdapter(['find401ByPid']);
        $adapter
            ->expects($this->once())
            ->method('find401ByPid')
            ->willReturn(null)
        ;
        $framework = $this->mockContaoFramework([PageModel::class => $adapter]);

        /** @var TokenInterface $token */
        $token = $this->mockToken(TwoFactorToken::class, true, $user);
        $request = $this->mockRequest(true, $pageModel);
        $event = $this->mockGetResponseEvent($request);
        $scopeMatcher = $this->mockScopeMatcher(true, $event);
        $tokenStorage = $this->mockTokenStorageWithToken($token);

        $listener = new TwoFactorFrontendListener($framework, $scopeMatcher, $tokenStorage);
        $listener->onKernelRequest($event);
    }

    public function testReturnsIfNoRedirectPageIsFound(): void
    {
        /** @var FrontendUser $user */
        $user = $this->mockClassWithProperties(FrontendUser::class, ['useTwoFactor' => true]);

        /** @var PageModel $pageModel */
        $pageModel = $this->mockClassWithProperties(PageModel::class, [
            'enforceTwoFactor' => false,
            'rootId' => 0,
        ]);

        $unauthorizedPageModel = $this->mockClassWithProperties(PageModel::class, ['jumpTo' => null]);

        $adapter = $this->mockAdapter(['find401ByPid', 'findPublishedById']);
        $adapter
            ->expects($this->once())
            ->method('find401ByPid')
            ->willReturn($unauthorizedPageModel)
        ;

        $adapter
            ->expects($this->once())
            ->method('findPublishedById')
            ->willReturn(null)
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $adapter]);

        /** @var TokenInterface $token */
        $token = $this->mockToken(TwoFactorToken::class, true, $user);
        $request = $this->mockRequest(true, $pageModel);
        $event = $this->mockGetResponseEvent($request);
        $scopeMatcher = $this->mockScopeMatcher(true, $event);
        $tokenStorage = $this->mockTokenStorageWithToken($token);

        $listener = new TwoFactorFrontendListener($framework, $scopeMatcher, $tokenStorage);
        $listener->onKernelRequest($event);
    }

    public function testReturnsIf401PageIsFound(): void
    {
        /** @var FrontendUser $user */
        $user = $this->mockClassWithProperties(FrontendUser::class, ['useTwoFactor' => true]);

        /** @var PageModel $pageModel */
        $pageModel = $this->mockClassWithProperties(PageModel::class, [
            'enforceTwoFactor' => false,
            'pageId' => 1,
            'rootId' => 0,
        ]);

        $unauthorizedPageModel = $this->mockClassWithProperties(PageModel::class, ['jumpTo' => 1]);
        $redirectPageModel = $this->mockClassWithProperties(PageModel::class, ['pageId' => 1]);

        $adapter = $this->mockAdapter(['find401ByPid', 'findPublishedById']);
        $adapter
            ->expects($this->once())
            ->method('find401ByPid')
            ->willReturn($unauthorizedPageModel)
        ;

        $adapter
            ->expects($this->once())
            ->method('findPublishedById')
            ->willReturn($redirectPageModel)
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $adapter]);

        /** @var TokenInterface $token */
        $token = $this->mockToken(TwoFactorToken::class, true, $user);
        $request = $this->mockRequest(true, $pageModel);
        $event = $this->mockGetResponseEvent($request);
        $scopeMatcher = $this->mockScopeMatcher(true, $event);
        $tokenStorage = $this->mockTokenStorageWithToken($token);

        $listener = new TwoFactorFrontendListener($framework, $scopeMatcher, $tokenStorage);
        $listener->onKernelRequest($event);
    }

    public function testThrowsUnauthorizedHttpExceptionIfNo401PageIsFound(): void
    {
        /** @var FrontendUser $user */
        $user = $this->mockClassWithProperties(FrontendUser::class, ['useTwoFactor' => true]);

        /** @var PageModel $pageModel */
        $pageModel = $this->mockClassWithProperties(PageModel::class, [
            'enforceTwoFactor' => false,
            'id' => 1,
            'rootId' => 0,
        ]);

        $unauthorizedPageModel = $this->mockClassWithProperties(PageModel::class, ['jumpTo' => 1]);
        $redirectPageModel = $this->mockClassWithProperties(PageModel::class, ['id' => 2]);

        $adapter = $this->mockAdapter(['find401ByPid', 'findPublishedById']);
        $adapter
            ->expects($this->once())
            ->method('find401ByPid')
            ->willReturn($unauthorizedPageModel)
        ;

        $adapter
            ->expects($this->once())
            ->method('findPublishedById')
            ->willReturn($redirectPageModel)
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $adapter]);

        /** @var TokenInterface $token */
        $token = $this->mockToken(TwoFactorToken::class, true, $user);
        $request = $this->mockRequest(true, $pageModel);
        $event = $this->mockGetResponseEvent($request);
        $scopeMatcher = $this->mockScopeMatcher(true, $event);
        $tokenStorage = $this->mockTokenStorageWithToken($token);

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Missing two-factor authentication.');

        $listener = new TwoFactorFrontendListener($framework, $scopeMatcher, $tokenStorage);
        $listener->onKernelRequest($event);
    }

    private function mockToken(string $class, bool $withFrontendUser = false, FrontendUser $user = null)
    {
        $token = $this->createMock($class);

        if (null === $user) {
            $user = $this->createMock(FrontendUser::class);
        }

        if ($withFrontendUser) {
            $token
                ->expects($this->once())
                ->method('getUser')
                ->willReturn($user)
            ;
        }

        return $token;
    }

    private function mockRequest(bool $withPageModel = false, PageModel $pageModel = null): Request
    {
        $request = new Request();
        $request->attributes->set('pageModel', null);

        if (null === $pageModel) {
            $pageModel = $this->createMock(PageModel::class);
        }

        if ($withPageModel) {
            $request->attributes->set('pageModel', $pageModel);
        }

        return $request;
    }

    private function mockTokenStorageWithToken(TokenInterface $token = null): TokenStorageInterface
    {
        $tokenStorage = $this->createMock(TokenStorage::class);

        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token)
        ;

        return $tokenStorage;
    }

    private function mockScopeMatcher(bool $hasFrontendUser, GetResponseEvent $event): ScopeMatcher
    {
        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->once())
            ->method('isFrontendMasterRequest')
            ->with($event)
            ->willReturn($hasFrontendUser)
        ;

        return $scopeMatcher;
    }

    private function mockGetResponseEvent(Request $request = null, Response $response = null): GetResponseEvent
    {
        $kernel = $this->createMock(Kernel::class);

        if (null === $request) {
            $request = new Request();
        }

        $event = new GetResponseEvent($kernel, $request, Kernel::MASTER_REQUEST);

        if (null !== $response) {
            $event->setResponse($response);
        }

        return $event;
    }
}
