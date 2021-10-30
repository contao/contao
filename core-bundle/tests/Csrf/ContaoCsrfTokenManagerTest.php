<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Csrf;

use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\Csrf\MemoryTokenStorage;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;

class ContaoCsrfTokenManagerTest extends TestCase
{
    public function testGetFrontendTokenValue(): void
    {
        $storage = new MemoryTokenStorage();
        $storage->initialize([
            'contao_csrf_token' => 'foo',
        ]);

        $tokenManager = new ContaoCsrfTokenManager(
            $this->createMock(RequestStack::class),
            'csrf_',
            $this->createMock(TokenGeneratorInterface::class),
            $storage,
            '',
            'contao_csrf_token'
        );

        $tokenValue = $tokenManager->getFrontendTokenValue();
        $token = new CsrfToken('contao_csrf_token', $tokenValue);

        $this->assertTrue($tokenManager->isTokenValid($token));
    }

    public function testGetFrontendTokenValueFailsIfTokenNameIsNotSet(): void
    {
        $tokenManager = new ContaoCsrfTokenManager(
            $this->createMock(RequestStack::class),
            'csrf_',
            $this->createMock(TokenGeneratorInterface::class),
            $this->createMock(TokenStorageInterface::class),
            '',
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The Contao CSRF token manager was not initialized with a frontend token name.');

        $tokenManager->getFrontendTokenValue();
    }
}
