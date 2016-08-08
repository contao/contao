<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Monolog;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\Monolog\ContaoTableProcessor;
use Contao\CoreBundle\Test\TestCase;
use Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

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
        $this->assertInstanceOf('Contao\CoreBundle\Monolog\ContaoTableProcessor', $this->createContaoTableProcessor());
    }

    /**
     * Tests the __invoke() method.
     */
    public function testInvokation()
    {
        $processor = $this->createContaoTableProcessor();

        $this->assertEmpty($processor([]));
        $this->assertEquals(['foo' => 'bar'], $processor(['foo' => 'bar']));
        $this->assertEquals(['context' => ['contao' => false]], $processor(['context' => ['contao' => false]]));
    }

    /**
     * Tests the action for different error levels.
     *
     * @param int    $logLevel
     * @param string $expectedAction
     *
     * @dataProvider actionLevelProvider
     */
    public function testActionOnErrorLevel($logLevel, $expectedAction)
    {
        $processor = $this->createContaoTableProcessor();

        $record = $processor(
            [
                'level' => $logLevel,
                'context' => ['contao' => new ContaoContext(__METHOD__)],
            ]
        );

        /** @var ContaoContext $context */
        $context = $record['extra']['contao'];

        $this->assertEquals($expectedAction, $context->getAction());
    }

    /**
     * Tests that an existing action is not changed.
     *
     * @param int $logLevel
     *
     * @dataProvider actionLevelProvider
     */
    public function testExistingActionIsNotChanged($logLevel)
    {
        $processor = $this->createContaoTableProcessor();

        $record = $processor(
            [
                'level' => $logLevel,
                'context' => ['contao' => new ContaoContext(__METHOD__, ContaoContext::CRON)],
            ]
        );

        /** @var ContaoContext $context */
        $context = $record['extra']['contao'];

        $this->assertEquals(ContaoContext::CRON, $context->getAction());
    }

    /**
     * Tests that an IP is added if there is no request.
     */
    public function testIpOnEmptyRequest()
    {
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');

        $requestStack
            ->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn(null)
        ;

        $processor = $this->createContaoTableProcessor($requestStack);

        /** @var ContaoContext $context */
        $context = $processor(['context' => ['contao' => new ContaoContext(__METHOD__)]])['extra']['contao'];

        $this->assertEquals('127.0.0.1', $context->getIp());
    }

    /**
     * Tests that IP addresses are anonymized.
     *
     * @param string $input
     * @param string $expected
     *
     * @dataProvider anonymizedIpProvider
     */
    public function testAnonymizesIp($input, $expected)
    {
        $request = new Request([], [], [], [], [], ['REMOTE_ADDR' => $input]);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $processor = $this->createContaoTableProcessor($requestStack, null, false);

        /** @var ContaoContext $context */
        $context = $processor(['context' => ['contao' => new ContaoContext(__METHOD__)]])['extra']['contao'];

        $this->assertEquals($input, $context->getIp());

        $processor = $this->createContaoTableProcessor($requestStack, null, true);

        /** @var ContaoContext $context */
        $context = $processor(['context' => ['contao' => new ContaoContext(__METHOD__)]])['extra']['contao'];

        $this->assertEquals($expected, $context->getIp());
    }

    /**
     * Tests that the browser is added.
     */
    public function testBrowser()
    {
        $request = new Request([], [], [], [], [], ['HTTP_USER_AGENT' => 'Contao test']);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $processor = $this->createContaoTableProcessor($requestStack);

        /** @var ContaoContext $context */
        $context = $processor(
            ['context' => ['contao' => new ContaoContext(__METHOD__, null, null, null, 'foobar')]]
        )['extra']['contao'];

        $this->assertEquals('foobar', $context->getBrowser());

        /** @var ContaoContext $context */
        $context = $processor(['context' => ['contao' => new ContaoContext(__METHOD__)]])['extra']['contao'];

        $this->assertEquals('Contao test', $context->getBrowser());

        $requestStack->pop();

        /** @var ContaoContext $context */
        $context = $processor(['context' => ['contao' => new ContaoContext(__METHOD__)]])['extra']['contao'];

        $this->assertEquals('N/A', $context->getBrowser());
    }

    /**
     * Tests that the username is added.
     */
    public function testUsername()
    {
        $token = $this->getMock('Contao\CoreBundle\Security\Authentication\ContaoToken', [], [], '', false);

        $token
            ->expects($this->any())
            ->method('getUsername')
            ->willReturn('k.jones')
        ;

        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken($token);

        $processor = $this->createContaoTableProcessor(null, $tokenStorage);

        /** @var ContaoContext $context */
        $context = $processor(
            ['context' => ['contao' => new ContaoContext(__METHOD__, null, 'foobar')]]
        )['extra']['contao'];

        $this->assertEquals('foobar', $context->getUsername());

        /** @var ContaoContext $context */
        $context = $processor(['context' => ['contao' => new ContaoContext(__METHOD__)]])['extra']['contao'];

        $this->assertEquals('k.jones', $context->getUsername());

        $tokenStorage->setToken(null);

        /** @var ContaoContext $context */
        $context = $processor(['context' => ['contao' => new ContaoContext(__METHOD__)]])['extra']['contao'];

        $this->assertEquals('N/A', $context->getUsername());
    }

    /**
     * Tests that the source is added.
     *
     * @param ContainerInterface $container
     * @param string|null        $contextSource
     * @param string|null        $expectedSource
     *
     * @dataProvider sourceProvider
     */
    public function testSource($container, $contextSource, $expectedSource)
    {
        $processor = $this->createContaoTableProcessor();
        $processor->setContainer($container);

        $result = $processor(
            ['context' => ['contao' => new ContaoContext(__METHOD__, null, null, null, null, $contextSource)]]
        );

        /** @var ContaoContext $context */
        $context = $result['extra']['contao'];

        $this->assertEquals($expectedSource, $context->getSource());
    }

    /**
     * Provides the test action levels.
     *
     * @return array
     */
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

    /**
     * Provides the anonymized IPs.
     *
     * @return array
     */
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

    /**
     * Provides the sources.
     *
     * @return array
     */
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

    /**
     * Mocks a Symfony container with scope.
     *
     * @param string $scope
     *
     * @return ContainerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockContainerWithScope($scope)
    {
        $request = new Request([], [], ['_scope' => $scope]);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');

        $container
            ->expects($this->any())
            ->method('get')
            ->with('request_stack')
            ->willReturn($requestStack)
        ;

        return $container;
    }

    /**
     * Creates the ContaoTableProcessor object.
     *
     * @param RequestStack          $requestStack
     * @param TokenStorageInterface $tokenStorage
     * @param bool                  $anonymizeIp
     *
     * @return ContaoTableProcessor
     */
    private function createContaoTableProcessor($requestStack = null, $tokenStorage = null, $anonymizeIp = true)
    {
        if (null === $requestStack) {
            $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
        }

        if (null === $tokenStorage) {
            $tokenStorage = $this->getMock(
                'Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface'
            );
        }

        return new ContaoTableProcessor($requestStack, $tokenStorage, $anonymizeIp);
    }
}
