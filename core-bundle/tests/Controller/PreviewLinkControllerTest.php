<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Controller;

use Contao\CoreBundle\Controller\PreviewLinkController;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Security\Authentication\FrontendPreviewAuthenticator;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\DBAL\Connection;
use Nyholm\Psr7\Uri;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Constraint\IsType;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PreviewLinkControllerTest extends TestCase
{
    #[DataProvider('authenticateGuestProvider')]
    public function testAuthenticatesGuest(string $url, bool $showUnpublished): void
    {
        $request = Request::create('/');

        $listener = new PreviewLinkController(
            $this->mockAuthenticator($showUnpublished),
            $this->mockUriSigner(true),
            $this->mockConnection(['url' => $url, 'showUnpublished' => $showUnpublished]),
        );

        $response = $listener($request, 42);

        $this->assertSame((string) (new Uri($url)), $response->getTargetUrl());
    }

    public static function authenticateGuestProvider(): iterable
    {
        yield 'show unpublished' => ['/foo/bär', true];
        yield 'hide unpublished' => ['/foo/baz', false];
    }

    public function testDeniesAccessWithoutSignedUrl(): void
    {
        $request = Request::create('/');

        $listener = new PreviewLinkController(
            $this->mockAuthenticator(null),
            $this->mockUriSigner(false),
            $this->mockConnection(null),
        );

        $this->expectException(AccessDeniedException::class);

        $listener($request, 42);
    }

    public function testThrowsNotFoundExceptionIfLinkIsNotFound(): void
    {
        $request = Request::create('/');

        $listener = new PreviewLinkController(
            $this->mockAuthenticator(null),
            $this->mockUriSigner(true),
            $this->mockConnection(false),
        );

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Preview link not found.');

        $listener($request, 42);
    }

    private function mockAuthenticator(bool|null $showUnpublished): FrontendPreviewAuthenticator&MockObject
    {
        $authenticator = $this->createMock(FrontendPreviewAuthenticator::class);
        $authenticator
            ->expects(null === $showUnpublished ? $this->never() : $this->once())
            ->method('authenticateFrontendGuest')
            ->with($showUnpublished)
        ;

        return $authenticator;
    }

    private function mockUriSigner(bool $checkSuccessful): UriSigner&MockObject
    {
        $uriSigner = $this->createMock(UriSigner::class);
        $uriSigner
            ->expects($this->once())
            ->method('checkRequest')
            ->with($this->isInstanceOf(Request::class))
            ->willReturn($checkSuccessful)
        ;

        return $uriSigner;
    }

    private function mockConnection(array|bool|null $link): Connection&MockObject
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects(null === $link ? $this->never() : $this->once())
            ->method('fetchAssociative')
            ->with(
                'SELECT * FROM tl_preview_link WHERE id = ? AND published = 1 AND expiresAt > UNIX_TIMESTAMP()',
                new IsType('array'),
            )
            ->willReturn($link)
        ;

        return $connection;
    }
}
