<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Security\Authentication\RememberMe;

use Contao\CoreBundle\Entity\RememberMe;
use Contao\CoreBundle\Fixtures\Security\User\ForwardCompatibilityUserInterface;
use Contao\CoreBundle\Fixtures\Security\User\ForwardCompatibilityUserProviderInterface;
use Contao\CoreBundle\Repository\RememberMeRepository;
use Contao\CoreBundle\Security\Authentication\RememberMe\ExpiringTokenBasedRememberMeServices;
use Contao\CoreBundle\Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bridge\PhpUnit\ClockMock;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CookieTheftException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\RememberMe\RememberMeServicesInterface;

class ExpiringTokenBasedRememberMeServicesTest extends TestCase
{
    private const SECRET = 'foobar';

    private ExpiringTokenBasedRememberMeServices $listener;

    /**
     * @var RememberMeRepository&MockObject
     */
    private RememberMeRepository $repository;

    private static array $options = [
        'name' => 'REMEMBERME',
        'lifetime' => 31536000,
        'path' => '/',
        'domain' => null,
        'secure' => false,
        'httponly' => true,
        'samesite' => null,
        'always_remember_me' => false,
        'remember_me_parameter' => 'autologin',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        ClockMock::withClockMock(1142164800);

        $this->repository = $this->createMock(RememberMeRepository::class);

        $user = $this->createMock(UserInterface::class);
        $user
            ->method('getRoles')
            ->willReturn([])
        ;

        $userProvider = $this->createMock(ForwardCompatibilityUserProviderInterface::class);
        $userProvider
            ->method('supportsClass')
            ->willReturn(true)
        ;

        $userProvider
            ->method('loadUserByIdentifier')
            ->willReturn($user)
        ;

        $this->listener = new ExpiringTokenBasedRememberMeServices(
            $this->repository,
            [$userProvider],
            self::SECRET,
            'contao_frontend',
            self::$options
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        ClockMock::withClockMock(false);
    }

    public function testExpiresExistingRecordsAndCreatesNewCookieWithNewDatabaseRecord(): void
    {
        $entity = $this->mockEntity('bar');
        $entity
            ->expects($this->once())
            ->method('setExpiresInSeconds')
            ->with(ExpiringTokenBasedRememberMeServices::EXPIRATION)
            ->willReturnSelf()
        ;

        $entity
            ->expects($this->once())
            ->method('cloneWithNewValue')
            ->willReturn($this->mockEntity('baz'))
        ;

        $this->expectTableLocking();
        $this->expectTableReturnsEntities($entity);

        $this->repository
            ->expects($this->once())
            ->method('persist')
            ->with($entity)
        ;

        $request = $this->mockRequestWithCookie();
        $token = $this->listener->autoLogin($request);

        $this->assertRequestHasRememberMeCookie($request);
        $this->assertInstanceOf(RememberMeToken::class, $token);
    }

    public function testUpdatesCookieValueFromDatabaseIfTwoRecordsExist(): void
    {
        $this->expectTableLocking();
        $this->expectTableReturnsEntities($this->mockEntity('baz'), $this->mockEntity('bar', (new \DateTime())->setTimestamp(time())));

        $request = $this->mockRequestWithCookie();
        $token = $this->listener->autoLogin($request);

        $this->assertRequestHasRememberMeCookie($request);
        $this->assertInstanceOf(RememberMeToken::class, $token);
    }

    public function testDeletesCookieIfSeriesIsNotFoundInDatabase(): void
    {
        $this->expectTableLocking();
        $this->expectSeriesIsDeleted();
        $this->expectTableReturnsEntities();

        $request = $this->mockRequestWithCookie();
        $this->listener->autoLogin($request);

        $this->assertCookieIsDeleted($request);
    }

    public function testDeletesCookieIfDatabaseRecordIsExpired(): void
    {
        $this->expectTableLocking();
        $this->expectSeriesIsDeleted();
        $this->expectTableReturnsEntities($this->mockEntity('bar', null, (new \DateTime())->setTimestamp(strtotime('-2 years', ClockMock::time()))));

        $request = $this->mockRequestWithCookie();
        $this->listener->autoLogin($request);

        $this->assertCookieIsDeleted($request);
    }

    public function testThrowsExceptionAndDeletesSeriesInDatabaseIfValueDoesNotMatch(): void
    {
        $this->expectTableLocking();
        $this->expectSeriesIsDeleted();
        $this->expectTableReturnsEntities($this->mockEntity('baz'));

        $request = $this->mockRequestWithCookie();

        $this->expectException(CookieTheftException::class);

        $this->listener->autoLogin($request);

        $this->assertCookieIsDeleted($request);
    }

    public function testDeletesCookieIfCookieContentIsNotValid(): void
    {
        $request = new Request();
        $request->cookies->set('REMEMBERME', base64_encode('foo'));

        $this->listener->autoLogin($request);

        $this->assertCookieIsDeleted($request);
    }

    public function testDeletesCookieOnLogout(): void
    {
        $this->expectSeriesIsDeleted();

        $request = $this->mockRequestWithCookie();
        $this->listener->logout($request, $this->createMock(Response::class), $this->createMock(TokenInterface::class));

        $this->assertCookieIsDeleted($request);
    }

    public function testCreatesDatabaseRecordAndCookieOnLogin(): void
    {
        /** @var RememberMe $entity */
        $entity = null;

        $request = new Request();
        $request->request->set('autologin', '1');

        $response = new Response();

        $user = $this->createMock(ForwardCompatibilityUserInterface::class);
        $user
            ->expects($this->once())
            ->method('getUserIdentifier')
            ->willReturn('foo')
        ;

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->atLeastOnce())
            ->method('getUser')
            ->willReturn($user)
        ;

        $this->repository
            ->expects($this->once())
            ->method('persist')
            ->willReturnCallback(
                static function (...$args) use (&$entity) {
                    $entity = $args[0];

                    return 1;
                }
            )
        ;

        $this->listener->loginSuccess($request, $response, $token);

        $this->assertInstanceOf(RememberMe::class, $entity);
        $this->assertResponseHasRememberMeCookie($response, $entity->getValue());
    }

    public function testDoesNothingOnLoginIfUserIsNotValid(): void
    {
        $request = new Request();
        $request->request->set('autologin', '1');

        $response = new Response();

        $token = $this->createMock(TokenInterface::class);

        $this->repository
            ->expects($this->never())
            ->method('persist')
        ;

        $this->listener->loginSuccess($request, $response, $token);
    }

    private function mockRequestWithCookie(): Request
    {
        $request = new Request();
        $value = implode('-', array_map('base64_encode', ['foo', 'bar']));

        $request->cookies->set('REMEMBERME', $value);

        return $request;
    }

    /**
     * @return RememberMe&MockObject
     */
    private function mockEntity(string $value, ?\DateTime $expires = null, ?\DateTime $lastUsed = null): RememberMe
    {
        $entity = $this->createMock(RememberMe::class);
        $entity
            ->method('getValue')
            ->willReturn($value)
        ;

        $entity
            ->method('getLastUsed')
            ->willReturn($lastUsed ?: (new \DateTime())->setTimestamp(time()))
        ;

        $entity
            ->method('getExpires')
            ->willReturn($expires)
        ;

        return $entity;
    }

    private function expectTableLocking(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('lockTable')
        ;

        $this->repository
            ->expects($this->once())
            ->method('unlockTable')
        ;
    }

    private function expectTableReturnsEntities(RememberMe ...$entities): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findBySeries')
            ->with(hash_hmac('sha256', 'foo', self::SECRET, true))
            ->willReturn($entities)
        ;

        // Expired records should always be deleted when current ones are searched for
        $this->repository
            ->expects($this->once())
            ->method('deleteExpired')
        ;
    }

    private function expectSeriesIsDeleted(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('deleteBySeries')
            ->with(hash_hmac('sha256', 'foo', self::SECRET, true))
        ;
    }

    private function assertCookieIsDeleted(Request $request): void
    {
        $this->assertTrue($request->attributes->has(RememberMeServicesInterface::COOKIE_ATTR_NAME));
        $this->assertInstanceOf(Cookie::class, $request->attributes->get(RememberMeServicesInterface::COOKIE_ATTR_NAME));
    }

    private function assertRequestHasRememberMeCookie(Request $request): void
    {
        $this->assertTrue($request->attributes->has(RememberMeServicesInterface::COOKIE_ATTR_NAME));

        /** @var Cookie $cookie */
        $cookie = $request->attributes->get(RememberMeServicesInterface::COOKIE_ATTR_NAME);

        $this->assertInstanceOf(Cookie::class, $cookie);
        $this->assertRememberMeCookie($cookie, 'foo', 'baz');
    }

    private function assertResponseHasRememberMeCookie(Response $response, string $value): void
    {
        $cookies = $response->headers->getCookies();

        $this->assertCount(1, $cookies);

        /** @var Cookie $cookie */
        $cookie = $cookies[0];

        $this->assertInstanceOf(Cookie::class, $cookie);
        $this->assertRememberMeCookie($cookie, null, $value);
    }

    private function assertRememberMeCookie(Cookie $cookie, ?string $series, string $value): void
    {
        $parts = array_map('base64_decode', explode('-', $cookie->getValue()));

        $this->assertSame($value, $parts[1]);

        if (null !== $series) {
            $this->assertSame($series, $parts[0]);
        }

        $this->assertSame(self::$options['name'], $cookie->getName());
        $this->assertSame(time() + self::$options['lifetime'], $cookie->getExpiresTime());
        $this->assertSame(self::$options['path'], $cookie->getPath());
        $this->assertSame(self::$options['domain'], $cookie->getDomain());
        $this->assertSame(self::$options['secure'], $cookie->isSecure());
        $this->assertSame(self::$options['httponly'], $cookie->isHttpOnly());
        $this->assertSame(self::$options['samesite'], $cookie->getSameSite());
    }
}
