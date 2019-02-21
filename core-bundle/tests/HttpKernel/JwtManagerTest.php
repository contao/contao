<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\HttpKernel;

use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\CoreBundle\HttpKernel\JwtManager;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class JwtManagerTest extends ContaoTestCase
{
    /**
     * @var JwtManager
     */
    private $jwtManager;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->jwtManager = new JwtManager($this->getTempDir());
    }

    public function testCreatesASecret(): void
    {
        $this->assertFileExists($this->getTempDir().'/var/jwt_secret');
    }

    public function testThrowsAnExceptionIfThereIsNoCookie(): void
    {
        $request = Request::create('/');

        $this->expectException(RedirectResponseException::class);

        $this->jwtManager->parseRequest($request);
    }

    public function testTokenCanBeSetWithoutPayload(): void
    {
        $response = new Response();
        $this->jwtManager->addResponseCookie($response);

        $request = Request::create('/');
        $request->cookies->set(JwtManager::COOKIE_NAME, $this->getCookieValueFromResponse($response));

        $result = $this->jwtManager->parseRequest($request);

        $this->assertArrayHasKey('iat', $result);
        $this->assertArrayHasKey('exp', $result);
    }

    public function testTokenCanBeSetWithPayload(): void
    {
        $response = new Response();
        $this->jwtManager->addResponseCookie($response, ['foo' => 'bar']);

        $request = Request::create('/');
        $request->cookies->set(JwtManager::COOKIE_NAME, $this->getCookieValueFromResponse($response));

        $result = $this->jwtManager->parseRequest($request);

        $this->assertArrayHasKey('iat', $result);
        $this->assertArrayHasKey('exp', $result);
        $this->assertArrayHasKey('foo', $result);
        $this->assertSame('bar', $result['foo']);
    }

    public function testClearTheResponseCookie(): void
    {
        $headerBag = $this->createMock(ResponseHeaderBag::class);
        $headerBag
            ->expects($this->once())
            ->method('clearCookie')
            ->with(JwtManager::COOKIE_NAME)
        ;

        $response = new Response();
        $response->headers = $headerBag;

        $this->jwtManager->clearResponseCookie($response);
    }

    private function getCookieValueFromResponse(Response $response): ?string
    {
        foreach ($response->headers->getCookies() as $cookie) {
            if (JwtManager::COOKIE_NAME === $cookie->getName()) {
                return $cookie->getValue();
            }
        }

        return null;
    }
}
