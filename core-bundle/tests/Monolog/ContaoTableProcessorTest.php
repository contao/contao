<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Monolog;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\Monolog\ContaoTableProcessor;
use Contao\CoreBundle\Tests\TestCase;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class ContaoTableProcessorTest extends TestCase
{
    public function testCanBeInvoked(): void
    {
        $processor = $this->mockContaoTableProcessor();

        $this->assertEmpty($processor([]));
        $this->assertSame(['foo' => 'bar'], $processor(['foo' => 'bar']));
        $this->assertSame(['context' => ['contao' => false]], $processor(['context' => ['contao' => false]]));
    }

    /**
     * @dataProvider actionLevelProvider
     */
    public function testReturnsDifferentActionsForDifferentErrorLevels(int $logLevel, string $expectedAction): void
    {
        $data = [
            'level' => $logLevel,
            'context' => ['contao' => new ContaoContext(__METHOD__)],
        ];

        $processor = $this->mockContaoTableProcessor();
        $record = $processor($data);

        /** @var ContaoContext $context */
        $context = $record['extra']['contao'];

        $this->assertSame($expectedAction, $context->getAction());
    }

    /**
     * @dataProvider actionLevelProvider
     */
    public function testDoesNotChangeAnExistingAction(int $logLevel): void
    {
        $data = [
            'level' => $logLevel,
            'context' => ['contao' => new ContaoContext(__METHOD__, ContaoContext::CRON)],
        ];

        $processor = $this->mockContaoTableProcessor();
        $record = $processor($data);

        /** @var ContaoContext $context */
        $context = $record['extra']['contao'];

        $this->assertSame(ContaoContext::CRON, $context->getAction());
    }

    public function actionLevelProvider(): \Generator
    {
        yield [Logger::DEBUG, ContaoContext::GENERAL];
        yield [Logger::INFO, ContaoContext::GENERAL];
        yield [Logger::NOTICE, ContaoContext::GENERAL];
        yield [Logger::WARNING, ContaoContext::GENERAL];
        yield [Logger::ERROR, ContaoContext::ERROR];
        yield [Logger::CRITICAL, ContaoContext::ERROR];
        yield [Logger::ALERT, ContaoContext::ERROR];
        yield [Logger::EMERGENCY, ContaoContext::ERROR];
    }

    public function testAddsTheUserAgent(): void
    {
        $request = new Request([], [], [], [], [], ['HTTP_USER_AGENT' => 'Contao test']);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $processor = $this->mockContaoTableProcessor($requestStack);

        $data = [
            'context' => [
                'contao' => new ContaoContext(__METHOD__, null, null, null, 'foobar'),
            ],
        ];

        $record = $processor($data);

        /** @var ContaoContext $context */
        $context = $record['extra']['contao'];

        $this->assertSame('foobar', $context->getBrowser());

        $data = [
            'context' => [
                'contao' => new ContaoContext(__METHOD__),
            ],
        ];

        $record = $processor($data);

        /** @var ContaoContext $context */
        $context = $record['extra']['contao'];

        $this->assertSame('Contao test', $context->getBrowser());

        $requestStack->pop();

        $data = [
            'context' => [
                'contao' => new ContaoContext(__METHOD__),
            ],
        ];

        $record = $processor($data);

        /** @var ContaoContext $context */
        $context = $record['extra']['contao'];

        $this->assertSame('N/A', $context->getBrowser());
    }

    public function testAddsTheUsername(): void
    {
        $token = $this->createMock(UsernamePasswordToken::class);
        $token
            ->method('getUsername')
            ->willReturn('k.jones')
        ;

        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken($token);

        $processor = $this->mockContaoTableProcessor(null, $tokenStorage);

        $data = [
            'context' => [
                'contao' => new ContaoContext(__METHOD__, null, 'foobar'),
            ],
        ];

        $record = $processor($data);

        /** @var ContaoContext $context */
        $context = $record['extra']['contao'];

        $this->assertSame('foobar', $context->getUsername());

        $data = [
            'context' => [
                'contao' => new ContaoContext(__METHOD__),
            ],
        ];

        $record = $processor($data);

        /** @var ContaoContext $context */
        $context = $record['extra']['contao'];

        $this->assertSame('k.jones', $context->getUsername());

        $tokenStorage->setToken();

        $data = [
            'context' => [
                'contao' => new ContaoContext(__METHOD__),
            ],
        ];

        $record = $processor($data);

        /** @var ContaoContext $context */
        $context = $record['extra']['contao'];

        $this->assertSame('N/A', $context->getUsername());
    }

    /**
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

        $data = [
            'context' => [
                'contao' => new ContaoContext(__METHOD__, null, null, null, null, $contextSource),
            ],
        ];

        $processor = $this->mockContaoTableProcessor($requestStack);
        $result = $processor($data);

        /** @var ContaoContext $context */
        $context = $result['extra']['contao'];

        $this->assertSame($expectedSource, $context->getSource());
    }

    public function sourceProvider(): \Generator
    {
        yield [null, 'FE', 'FE'];
        yield [null, 'BE', 'BE'];
        yield [null, null, 'FE'];
        yield [ContaoCoreBundle::SCOPE_FRONTEND, 'FE', 'FE'];
        yield [ContaoCoreBundle::SCOPE_FRONTEND, 'BE', 'BE'];
        yield [ContaoCoreBundle::SCOPE_FRONTEND, null, 'FE'];
        yield [ContaoCoreBundle::SCOPE_BACKEND, 'FE', 'FE'];
        yield [ContaoCoreBundle::SCOPE_BACKEND, 'BE', 'BE'];
        yield [ContaoCoreBundle::SCOPE_BACKEND, null, 'BE'];
    }

    private function mockContaoTableProcessor(RequestStack $requestStack = null, TokenStorageInterface $tokenStorage = null): ContaoTableProcessor
    {
        if (null === $requestStack) {
            $requestStack = $this->createMock(RequestStack::class);
        }

        if (null === $tokenStorage) {
            $tokenStorage = $this->createMock(TokenStorageInterface::class);
        }

        return new ContaoTableProcessor($requestStack, $tokenStorage, $this->mockScopeMatcher());
    }
}
