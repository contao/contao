<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Tests\HttpKernel;

use Contao\ManagerBundle\HttpKernel\JwtManager;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class JwtManagerTest extends ContaoTestCase
{
    public function testCreatesASecret(): void
    {
        $tempDir = static::getTempDir();

        new JwtManager($tempDir);

        $this->assertFileExists($tempDir.'/var/jwt_secret');
    }

    public function testTokenCanBeSetWithoutPayload(): void
    {
        $response = new Response();

        $jwtManager = new JwtManager(static::getTempDir());
        $jwtManager->addResponseCookie($response);

        $request = Request::create('/');
        $request->cookies->set(JwtManager::COOKIE_NAME, $this->getCookieValueFromResponse($response));

        $result = $jwtManager->parseRequest($request);

        $this->assertArrayHasKey('iat', $result);
        $this->assertArrayHasKey('exp', $result);
    }

    public function testTokenCanBeSetWithPayload(): void
    {
        $response = new Response();

        $jwtManager = new JwtManager(static::getTempDir());
        $jwtManager->addResponseCookie($response, ['foo' => 'bar']);

        $request = Request::create('/');
        $request->cookies->set(JwtManager::COOKIE_NAME, $this->getCookieValueFromResponse($response));

        $result = $jwtManager->parseRequest($request);

        $this->assertArrayHasKey('iat', $result);
        $this->assertArrayHasKey('exp', $result);
        $this->assertArrayHasKey('foo', $result);
        $this->assertSame('bar', $result['foo']);
    }

    public function testDoesNotOverwriteExistingCookie(): void
    {
        $jwtManager = new JwtManager(static::getTempDir());

        $headerBag = $this->createMock(ResponseHeaderBag::class);
        $headerBag
            ->expects($this->once())
            ->method('getCookies')
            ->willReturn([Cookie::create(JwtManager::COOKIE_NAME, 'foobar')])
        ;

        $headerBag
            ->expects($this->never())
            ->method('setCookie')
        ;

        $response = new Response();
        $response->headers = $headerBag;

        $jwtManager->addResponseCookie($response, ['foo' => 'bar']);
    }

    public function testReturnsNullWithoutCookie(): void
    {
        $request = Request::create('/');
        $request->cookies->set(JwtManager::COOKIE_NAME, 'foobar');

        $jwtManager = new JwtManager(static::getTempDir());
        $result = $jwtManager->parseRequest($request);

        $this->assertNull($result);
    }

    public function testIgnoresInvalidCookieData(): void
    {
        $request = Request::create('/');
        $jwtManager = new JwtManager(static::getTempDir());
        $result = $jwtManager->parseRequest($request);

        $this->assertNull($result);
    }

    public function testClearTheResponseCookie(): void
    {
        $headerBag = $this->createMock(ResponseHeaderBag::class);
        $headerBag
            ->expects($this->once())
            ->method('clearCookie')
            ->with(JwtManager::COOKIE_NAME)
        ;

        $headerBag
            ->expects($this->once())
            ->method('getCookies')
            ->willReturn([])
        ;

        $response = new Response();
        $response->headers = $headerBag;

        $jwtManager = new JwtManager(static::getTempDir());
        $jwtManager->clearResponseCookie($response);
    }

    public function testDoesNotClearTheResponseCookieIfThereIsAJwtCookie(): void
    {
        $headerBag = $this->createMock(ResponseHeaderBag::class);
        $headerBag
            ->expects($this->never())
            ->method('clearCookie')
            ->with(JwtManager::COOKIE_NAME)
        ;

        $headerBag
            ->expects($this->once())
            ->method('getCookies')
            ->willReturn([Cookie::create(JwtManager::COOKIE_NAME, 'foobar')])
        ;

        $response = new Response();
        $response->headers = $headerBag;

        $jwtManager = new JwtManager(static::getTempDir());
        $jwtManager->clearResponseCookie($response);
    }

    private function getCookieValueFromResponse(Response $response): string|null
    {
        foreach ($response->headers->getCookies() as $cookie) {
            if (JwtManager::COOKIE_NAME === $cookie->getName()) {
                return $cookie->getValue();
            }
        }

        return null;
    }
}
