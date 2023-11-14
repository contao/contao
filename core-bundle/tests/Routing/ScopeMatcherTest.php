<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Routing;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class ScopeMatcherTest extends TestCase
{
    private ScopeMatcher $matcher;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->matcher = $this->mockScopeMatcher();
    }

    /**
     * @dataProvider mainRequestProvider
     */
    public function testRecognizesTheContaoScopes(string|null $scope, int $requestType, bool $isMain, bool $isFrontend, bool $isBackend): void
    {
        $request = new Request();
        $request->attributes->set('_scope', $scope);

        $event = new KernelEvent($this->createMock(KernelInterface::class), $request, $requestType);

        $this->assertSame($isMain, $this->matcher->isContaoMainRequest($event));
        $this->assertSame($isMain && $isBackend, $this->matcher->isBackendMainRequest($event));
        $this->assertSame($isMain && $isFrontend, $this->matcher->isFrontendMainRequest($event));
        $this->assertSame($isBackend, $this->matcher->isBackendRequest($request));
        $this->assertSame($isFrontend, $this->matcher->isFrontendRequest($request));
    }

    public function mainRequestProvider(): \Generator
    {
        yield [
            ContaoCoreBundle::SCOPE_BACKEND,
            HttpKernelInterface::MAIN_REQUEST,
            true,
            false,
            true,
        ];

        yield [
            ContaoCoreBundle::SCOPE_FRONTEND,
            HttpKernelInterface::MAIN_REQUEST,
            true,
            true,
            false,
        ];

        yield [
            null,
            HttpKernelInterface::MAIN_REQUEST,
            false,
            false,
            false,
        ];

        yield [
            ContaoCoreBundle::SCOPE_BACKEND,
            HttpKernelInterface::SUB_REQUEST,
            false,
            false,
            true,
        ];

        yield [
            ContaoCoreBundle::SCOPE_FRONTEND,
            HttpKernelInterface::SUB_REQUEST,
            false,
            true,
            false,
        ];

        yield [
            null,
            HttpKernelInterface::SUB_REQUEST,
            false,
            false,
            false,
        ];
    }
}
