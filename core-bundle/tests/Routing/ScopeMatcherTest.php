<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Routing;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Tests the ScopeMatcher class.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class ScopeMatcherTest extends TestCase
{
    /**
     * @var ScopeMatcher
     */
    private $matcher;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->matcher = $this->mockScopeMatcher();
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\CoreBundle\Routing\ScopeMatcher', $this->matcher);
    }

    /**
     * Tests the request methods.
     *
     * @param string $scope
     * @param string $requestType
     * @param bool   $isMaster
     * @param bool   $isFrontend
     * @param bool   $isBackend
     *
     * @dataProvider masterRequestProvider
     */
    public function testRequestMethods($scope, $requestType, $isMaster, $isFrontend, $isBackend)
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

    /**
     * Provides the data for the request tests.
     *
     * @return array
     */
    public function masterRequestProvider()
    {
        return [
            [
                ContaoCoreBundle::SCOPE_BACKEND,
                HttpKernelInterface::MASTER_REQUEST,
                true,
                false,
                true,
            ],
            [
                ContaoCoreBundle::SCOPE_FRONTEND,
                HttpKernelInterface::MASTER_REQUEST,
                true,
                true,
                false,
            ],
            [
                null,
                HttpKernelInterface::MASTER_REQUEST,
                false,
                false,
                false,
            ],
            [
                ContaoCoreBundle::SCOPE_BACKEND,
                HttpKernelInterface::SUB_REQUEST,
                false,
                false,
                true,
            ],
            [
                ContaoCoreBundle::SCOPE_FRONTEND,
                HttpKernelInterface::SUB_REQUEST,
                false,
                true,
                false,
            ],
            [
                null,
                HttpKernelInterface::SUB_REQUEST,
                false,
                false,
                false,
            ],
        ];
    }
}
