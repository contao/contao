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
    /**
     * @var ScopeMatcher
     */
    private $matcher;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->matcher = $this->mockScopeMatcher();
    }

    /**
     * @dataProvider masterRequestProvider
     */
    public function testRecognizesTheContaoScopes(?string $scope, int $requestType, bool $isMaster, bool $isFrontend, bool $isBackend): void
    {
        $request = new Request();
        $request->attributes->set('_scope', $scope);

        $event = new KernelEvent($this->createMock(KernelInterface::class), $request, $requestType);

        $this->assertSame($isMaster, $this->matcher->isContaoMasterRequest($event));
        $this->assertSame($isMaster && $isBackend, $this->matcher->isBackendMasterRequest($event));
        $this->assertSame($isMaster && $isFrontend, $this->matcher->isFrontendMasterRequest($event));
        $this->assertSame($isBackend, $this->matcher->isBackendRequest($request));
        $this->assertSame($isFrontend, $this->matcher->isFrontendRequest($request));
    }

    public function masterRequestProvider(): \Generator
    {
        yield [
            ContaoCoreBundle::SCOPE_BACKEND,
            HttpKernelInterface::MASTER_REQUEST,
            true,
            false,
            true,
        ];

        yield [
            ContaoCoreBundle::SCOPE_FRONTEND,
            HttpKernelInterface::MASTER_REQUEST,
            true,
            true,
            false,
        ];

        yield [
            null,
            HttpKernelInterface::MASTER_REQUEST,
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
