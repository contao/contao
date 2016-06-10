<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Monolog;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\Monolog\ContaoTableProcessor;
use Contao\CoreBundle\Test\TestCase;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

/**
 * Tests the ContaoTableProcessor class.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class ContaoTableProcessorTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $processor = new ContaoTableProcessor(
            $this->getMock('Symfony\Component\HttpFoundation\RequestStack'),
            $this->getMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface')
        );

        $this->assertInstanceOf('Contao\CoreBundle\Monolog\ContaoTableProcessor', $processor);
    }

    public function testIgnoresWithoutContext()
    {
        $processor = new ContaoTableProcessor(
            $this->getMock('Symfony\Component\HttpFoundation\RequestStack'),
            $this->getMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface')
        );

        $this->assertEmpty($processor([]));
        $this->assertEquals(['foo' => 'bar'], $processor(['foo' => 'bar']));
        $this->assertEquals(['context' => ['contao' => false]], $processor(['context' => ['contao' => false]]));
    }

    /**
     * @dataProvider actionLevelProvider
     */
    public function testActionOnErrorLevel($logLevel, $expectedAction)
    {
        $processor = new ContaoTableProcessor(
            $this->getMock('Symfony\Component\HttpFoundation\RequestStack'),
            $this->getMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface')
        );

        $record = $processor(
            [
                'level' => $logLevel,
                'context' => ['contao' => new ContaoContext(__METHOD__)]
            ]
        );

        /** @var ContaoContext $context */
        $context = $record['extra']['contao'];

        $this->assertEquals($expectedAction, $context->getAction());
    }

    /**
     * @dataProvider actionLevelProvider
     */
    public function testExistingActionIsNotChanged($logLevel)
    {
        $processor = new ContaoTableProcessor(
            $this->getMock('Symfony\Component\HttpFoundation\RequestStack'),
            $this->getMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface')
        );

        $record = $processor(
            [
                'level' => $logLevel,
                'context' => ['contao' => new ContaoContext(__METHOD__, ContaoContext::CRON)]
            ]
        );

        /** @var ContaoContext $context */
        $context = $record['extra']['contao'];

        $this->assertEquals(ContaoContext::CRON, $context->getAction());
    }

    public function testIpOnEmptyRequest()
    {
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');

        $requestStack
            ->method('getCurrentRequest')
            ->willReturn(null)
        ;
        
        $processor = new ContaoTableProcessor(
            $requestStack,
            $this->getMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface')
        );

        /** @var ContaoContext $context */
        $context = $processor(['context' => ['contao' => new ContaoContext(__METHOD__)]])['extra']['contao'];

        $this->assertEquals('127.0.0.1', $context->getIp());
    }

    /**
     * @dataProvider anonymizedIpProvider
     */
    public function testAnonymizesIp($input, $expected)
    {
        $request = new Request([], [], [], [], [], ['REMOTE_ADDR' => $input]);
        $requestStack = new RequestStack();

        $requestStack->push($request);

        $processor = new ContaoTableProcessor(
            $requestStack,
            $this->getMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface'),
            false
        );

        /** @var ContaoContext $context */
        $context = $processor(['context' => ['contao' => new ContaoContext(__METHOD__)]])['extra']['contao'];

        $this->assertEquals($input, $context->getIp());

        $processor = new ContaoTableProcessor(
            $requestStack,
            $this->getMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface'),
            true
        );

        /** @var ContaoContext $context */
        $context = $processor(['context' => ['contao' => new ContaoContext(__METHOD__)]])['extra']['contao'];

        $this->assertEquals($expected, $context->getIp());
    }

    public function testBrowser()
    {
        $request = new Request([], [], [], [], [], ['HTTP_USER_AGENT' => 'Contao Test']);
        $requestStack = new RequestStack();

        $requestStack->push($request);

        $processor = new ContaoTableProcessor(
            $requestStack,
            $this->getMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface')
        );

        /** @var ContaoContext $context */
        $context = $processor(
                       ['context' => ['contao' => new ContaoContext(__METHOD__, null, null, null, 'foobar')]]
                   )['extra']['contao'];

        $this->assertEquals('foobar', $context->getBrowser());

        /** @var ContaoContext $context */
        $context = $processor(['context' => ['contao' => new ContaoContext(__METHOD__)]])['extra']['contao'];

        $this->assertEquals('Contao Test', $context->getBrowser());

        $requestStack->pop();

        /** @var ContaoContext $context */
        $context = $processor(['context' => ['contao' => new ContaoContext(__METHOD__)]])['extra']['contao'];

        $this->assertEquals('N/A', $context->getBrowser());
    }

    public function testUsername()
    {
        $token        = $this->getMock('Contao\CoreBundle\Security\Authentication\ContaoToken', [], [], '', false);
        $tokenStorage = new TokenStorage();

        $token
            ->method('getUsername')
            ->willReturn('Contao Test')
        ;

        $tokenStorage->setToken($token);

        $processor = new ContaoTableProcessor(
            $this->getMock('Symfony\Component\HttpFoundation\RequestStack'),
            $tokenStorage
        );

        /** @var ContaoContext $context */
        $context = $processor(['context' => ['contao' => new ContaoContext(__METHOD__, null, 'foobar')]])['extra']['contao'];

        $this->assertEquals('foobar', $context->getUsername());

        /** @var ContaoContext $context */
        $context = $processor(['context' => ['contao' => new ContaoContext(__METHOD__)]])['extra']['contao'];

        $this->assertEquals('Contao Test', $context->getUsername());

        $tokenStorage->setToken(null);

        /** @var ContaoContext $context */
        $context = $processor(['context' => ['contao' => new ContaoContext(__METHOD__)]])['extra']['contao'];

        $this->assertEquals('N/A', $context->getUsername());
    }

    /**
     * @dataProvider sourceProvider
     */
    public function testSource($container, $contextSource, $expectedSource)
    {
        $processor = new ContaoTableProcessor(
            $this->getMock('Symfony\Component\HttpFoundation\RequestStack'),
            $this->getMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface')
        );

        $processor->setContainer($container);
        $result = $processor(
            ['context' => ['contao' => new ContaoContext(__METHOD__, null, null, null, null, $contextSource)]]
        );

        /** @var ContaoContext $context */
        $context = $result['extra']['contao'];

        $this->assertEquals($expectedSource, $context->getSource());
    }

    public function actionLevelProvider()
    {
        return [
            [Logger::DEBUG, ContaoContext::GENERAL],
            [Logger::INFO, ContaoContext::GENERAL],
            [Logger::NOTICE, ContaoContext::GENERAL],
            [Logger::WARNING, ContaoContext::GENERAL],
            [Logger::ERROR, ContaoContext::ERROR],
            [Logger::CRITICAL, ContaoContext::ERROR],
            [Logger::ALERT, ContaoContext::ERROR],
            [Logger::EMERGENCY, ContaoContext::ERROR],
        ];
    }

    public function anonymizedIpProvider()
    {
        return [
            ['127.0.0.1', '127.0.0.1'],
            ['::1', '::1'],
            ['192.168.1.111', '192.168.1.0'],
            ['10.10.10.10', '10.10.10.0'],
            ['FE80:0000:0000:0000:0202:B3FF:FE1E:8329', 'FE80:0000:0000:0000:0202:B3FF:FE1E:0000'],
            ['FE80::0202:B3FF:FE1E:8329', 'FE80::0202:B3FF:FE1E:0000'],
            ['2001:DB8:0:1', '2001:DB8:0:0000'],
            ['3ffe:1900:4545:3:200:f8ff:fe21:67cf', '3ffe:1900:4545:3:200:f8ff:fe21:0000'],
            ['fe80:0:0:0:200:f8ff:fe21:67cf', 'fe80:0:0:0:200:f8ff:fe21:0000'],
            ['fe80::200:f8ff:fe21:67cf', 'fe80::200:f8ff:fe21:0000'],
        ];
    }

    public function sourceProvider()
    {
        return [
            [null, 'FE', 'FE'],
            [null, 'BE', 'BE'],
            [null, null, 'FE'],

            [$this->mockContainerWithScope(ContaoCoreBundle::SCOPE_FRONTEND), 'FE', 'FE'],
            [$this->mockContainerWithScope(ContaoCoreBundle::SCOPE_FRONTEND), 'BE', 'BE'],
            [$this->mockContainerWithScope(ContaoCoreBundle::SCOPE_FRONTEND), null, 'FE'],

            [$this->mockContainerWithScope(ContaoCoreBundle::SCOPE_BACKEND), 'FE', 'FE'],
            [$this->mockContainerWithScope(ContaoCoreBundle::SCOPE_BACKEND), 'BE', 'BE'],
            [$this->mockContainerWithScope(ContaoCoreBundle::SCOPE_BACKEND), null, 'BE'],
        ];
    }

    private function mockContainerWithScope($scope)
    {
        $requestStack = new RequestStack();
        $request = new Request([], [], ['_scope' => $scope]);

        $requestStack->push($request);

        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');

        $container
            ->method('get')
            ->with('request_stack')
            ->willReturn($requestStack)
        ;

        return $container;
    }
}
