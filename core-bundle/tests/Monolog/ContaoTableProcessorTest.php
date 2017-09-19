<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Monolog;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\Monolog\ContaoTableProcessor;
use Contao\CoreBundle\Security\Authentication\ContaoToken;
use Contao\CoreBundle\Tests\TestCase;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Tests the ContaoTableProcessor class.
 */
class ContaoTableProcessorTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf('Contao\CoreBundle\Monolog\ContaoTableProcessor', $this->createContaoTableProcessor());
    }

    /**
     * Tests the __invoke() method.
     */
    public function testCanBeInvoked(): void
    {
        $processor = $this->createContaoTableProcessor();

        $this->assertEmpty($processor([]));
        $this->assertSame(['foo' => 'bar'], $processor(['foo' => 'bar']));
        $this->assertSame(['context' => ['contao' => false]], $processor(['context' => ['contao' => false]]));
    }

    /**
     * Tests the action for different error levels.
     *
     * @param int    $logLevel
     * @param string $expectedAction
     *
     * @dataProvider actionLevelProvider
     */
    public function testReturnsDifferentActionsForDifferentErrorLevels(int $logLevel, string $expectedAction): void
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

        $this->assertSame($expectedAction, $context->getAction());
    }

    /**
     * Tests that an existing action is not changed.
     *
     * @param int $logLevel
     *
     * @dataProvider actionLevelProvider
     */
    public function testDoesNotChangeAnExistingAction(int $logLevel): void
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

        $this->assertSame(ContaoContext::CRON, $context->getAction());
    }

    /**
     * Provides the test action levels.
     *
     * @return array
     */
    public function actionLevelProvider(): array
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
     * Tests that an IP is added if there is no request.
     */
    public function testAddsAnIpAddressIfThereIsNoRequest(): void
    {
        $processor = $this->createContaoTableProcessor();

        /** @var ContaoContext $context */
        $context = $processor(['context' => ['contao' => new ContaoContext(__METHOD__)]])['extra']['contao'];

        $this->assertSame('127.0.0.1', $context->getIp());
    }

    /**
     * Tests that IP addresses are anonymized.
     *
     * @param string $input
     * @param string $expected
     *
     * @dataProvider anonymizedIpProvider
     */
    public function testAnonymizesIpAddresses(string $input, string $expected): void
    {
        $request = new Request([], [], [], [], [], ['REMOTE_ADDR' => $input]);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $processor = $this->createContaoTableProcessor($requestStack, null, false);

        /** @var ContaoContext $context */
        $context = $processor(['context' => ['contao' => new ContaoContext(__METHOD__)]])['extra']['contao'];

        $this->assertSame($input, $context->getIp());

        $processor = $this->createContaoTableProcessor($requestStack, null, true);

        /** @var ContaoContext $context */
        $context = $processor(['context' => ['contao' => new ContaoContext(__METHOD__)]])['extra']['contao'];

        $this->assertSame($expected, $context->getIp());
    }

    /**
     * Provides the anonymized IPs.
     *
     * @return array
     */
    public function anonymizedIpProvider(): array
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
     * Tests that the browser is added.
     */
    public function testAddsTheUserAgent(): void
    {
        $request = new Request([], [], [], [], [], ['HTTP_USER_AGENT' => 'Contao test']);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $processor = $this->createContaoTableProcessor($requestStack);

        /** @var ContaoContext $context */
        $context = $processor(
            ['context' => ['contao' => new ContaoContext(__METHOD__, null, null, null, 'foobar')]]
        )['extra']['contao'];

        $this->assertSame('foobar', $context->getBrowser());

        /** @var ContaoContext $context */
        $context = $processor(['context' => ['contao' => new ContaoContext(__METHOD__)]])['extra']['contao'];

        $this->assertSame('Contao test', $context->getBrowser());

        $requestStack->pop();

        /** @var ContaoContext $context */
        $context = $processor(['context' => ['contao' => new ContaoContext(__METHOD__)]])['extra']['contao'];

        $this->assertSame('N/A', $context->getBrowser());
    }

    /**
     * Tests that the username is added.
     */
    public function testAddsTheUsername(): void
    {
        $token = $this->createMock(ContaoToken::class);

        $token
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

        $this->assertSame('foobar', $context->getUsername());

        /** @var ContaoContext $context */
        $context = $processor(['context' => ['contao' => new ContaoContext(__METHOD__)]])['extra']['contao'];

        $this->assertSame('k.jones', $context->getUsername());

        $tokenStorage->setToken(null);

        /** @var ContaoContext $context */
        $context = $processor(['context' => ['contao' => new ContaoContext(__METHOD__)]])['extra']['contao'];

        $this->assertSame('N/A', $context->getUsername());
    }

    /**
     * Tests that the source is added.
     *
     * @param string|null $scope
     * @param string|null $contextSource
     * @param string      $expectedSource
     *
     * @dataProvider sourceProvider
     */
    public function testAddsTheSource(?string $scope, ?string $contextSource, string $expectedSource): void
    {
        $requestStack = new RequestStack();

        if (null !== $scope) {
            $request = new Request();
            $request->attributes->set('_scope', $scope);

            $requestStack->push($request);
        }

        $processor = $this->createContaoTableProcessor($requestStack);

        $result = $processor(
            ['context' => ['contao' => new ContaoContext(__METHOD__, null, null, null, null, $contextSource)]]
        );

        /** @var ContaoContext $context */
        $context = $result['extra']['contao'];

        $this->assertSame($expectedSource, $context->getSource());
    }

    /**
     * Provides the sources.
     *
     * @return array
     */
    public function sourceProvider(): array
    {
        return [
            [null, 'FE', 'FE'],
            [null, 'BE', 'BE'],
            [null, null, 'FE'],

            [ContaoCoreBundle::SCOPE_FRONTEND, 'FE', 'FE'],
            [ContaoCoreBundle::SCOPE_FRONTEND, 'BE', 'BE'],
            [ContaoCoreBundle::SCOPE_FRONTEND, null, 'FE'],

            [ContaoCoreBundle::SCOPE_BACKEND, 'FE', 'FE'],
            [ContaoCoreBundle::SCOPE_BACKEND, 'BE', 'BE'],
            [ContaoCoreBundle::SCOPE_BACKEND, null, 'BE'],
        ];
    }

    /**
     * Creates the ContaoTableProcessor object.
     *
     * @param RequestStack|null          $requestStack
     * @param TokenStorageInterface|null $tokenStorage
     * @param bool                       $anonymizeIp
     *
     * @return ContaoTableProcessor
     */
    private function createContaoTableProcessor(RequestStack $requestStack = null, TokenStorageInterface $tokenStorage = null, bool $anonymizeIp = true): ContaoTableProcessor
    {
        if (null === $requestStack) {
            $requestStack = $this->createMock(RequestStack::class);
        }

        if (null === $tokenStorage) {
            $tokenStorage = $this->createMock(TokenStorageInterface::class);
        }

        return new ContaoTableProcessor($requestStack, $tokenStorage, $this->mockScopeMatcher(), $anonymizeIp);
    }
}
