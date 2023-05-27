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
use Contao\PageModel;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * @phpstan-import-type Record from Logger
 */
class ContaoTableProcessorTest extends TestCase
{
    public function testCanBeInvoked(): void
    {
        $processor = $this->getContaoTableProcessor();

        $record = [
            'message' => '',
            'context' => ['contao' => false],
            'level' => Logger::DEBUG,
            'level_name' => 'DEBUG',
            'channel' => '',
            'datetime' => new \DateTimeImmutable(),
            'extra' => [],
        ];

        $this->assertSame($record, $processor($record));
    }

    /**
     * @dataProvider actionLevelProvider
     *
     * @phpstan-param 100|200|250|300|400|500|550|600                                          $logLevel
     * @phpstan-param 'ALERT'|'CRITICAL'|'DEBUG'|'EMERGENCY'|'ERROR'|'INFO'|'NOTICE'|'WARNING' $logLevelName
     */
    public function testReturnsDifferentActionsForDifferentErrorLevels(int $logLevel, string $logLevelName, string $expectedAction): void
    {
        $data = [
            'message' => '',
            'context' => ['contao' => new ContaoContext(__METHOD__)],
            'level' => $logLevel,
            'level_name' => $logLevelName,
            'channel' => '',
            'datetime' => new \DateTimeImmutable(),
            'extra' => [],
        ];

        $processor = $this->getContaoTableProcessor();
        $record = $processor($data);

        /** @var ContaoContext $context */
        $context = $record['extra']['contao'];

        $this->assertSame($expectedAction, $context->getAction());
    }

    /**
     * @dataProvider actionLevelProvider
     *
     * @phpstan-param 100|200|250|300|400|500|550|600                                          $logLevel
     * @phpstan-param 'ALERT'|'CRITICAL'|'DEBUG'|'EMERGENCY'|'ERROR'|'INFO'|'NOTICE'|'WARNING' $logLevelName
     */
    public function testDoesNotChangeAnExistingAction(int $logLevel, string $logLevelName): void
    {
        $data = [
            'message' => '',
            'context' => ['contao' => new ContaoContext(__METHOD__, ContaoContext::CRON)],
            'level' => $logLevel,
            'level_name' => $logLevelName,
            'channel' => '',
            'datetime' => new \DateTimeImmutable(),
            'extra' => [],
        ];

        $processor = $this->getContaoTableProcessor();
        $record = $processor($data);

        /** @var ContaoContext $context */
        $context = $record['extra']['contao'];

        $this->assertSame(ContaoContext::CRON, $context->getAction());
    }

    public function actionLevelProvider(): \Generator
    {
        yield [Logger::DEBUG, 'DEBUG', ContaoContext::GENERAL];
        yield [Logger::INFO, 'INFO', ContaoContext::GENERAL];
        yield [Logger::NOTICE, 'NOTICE', ContaoContext::GENERAL];
        yield [Logger::WARNING, 'WARNING', ContaoContext::GENERAL];
        yield [Logger::ERROR, 'ERROR', ContaoContext::ERROR];
        yield [Logger::CRITICAL, 'CRITICAL', ContaoContext::ERROR];
        yield [Logger::ALERT, 'ALERT', ContaoContext::ERROR];
        yield [Logger::EMERGENCY, 'EMERGENCY', ContaoContext::ERROR];
    }

    public function testAddsTheUserAgent(): void
    {
        $request = new Request([], [], [], [], [], ['HTTP_USER_AGENT' => 'Contao test']);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $processor = $this->getContaoTableProcessor($requestStack);

        $data = [
            'message' => '',
            'context' => [
                'contao' => new ContaoContext(__METHOD__, null, null, null, 'foobar'),
            ],
            'level' => Logger::DEBUG,
            'level_name' => 'DEBUG',
            'channel' => '',
            'datetime' => new \DateTimeImmutable(),
            'extra' => [],
        ];

        $record = $processor($data);

        /** @var ContaoContext $context */
        $context = $record['extra']['contao'];

        $this->assertSame('foobar', $context->getBrowser());

        $data = [
            'message' => '',
            'context' => [
                'contao' => new ContaoContext(__METHOD__),
            ],
            'level' => Logger::DEBUG,
            'level_name' => 'DEBUG',
            'channel' => '',
            'datetime' => new \DateTimeImmutable(),
            'extra' => [],
        ];

        $record = $processor($data);

        /** @var ContaoContext $context */
        $context = $record['extra']['contao'];

        $this->assertSame('Contao test', $context->getBrowser());

        $requestStack->pop();

        $data = [
            'message' => '',
            'context' => [
                'contao' => new ContaoContext(__METHOD__),
            ],
            'level' => Logger::DEBUG,
            'level_name' => 'DEBUG',
            'channel' => '',
            'datetime' => new \DateTimeImmutable(),
            'extra' => [],
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
            ->method('getUserIdentifier')
            ->willReturn('k.jones')
        ;

        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken($token);

        $processor = $this->getContaoTableProcessor(null, $tokenStorage);

        $data = [
            'message' => '',
            'context' => [
                'contao' => new ContaoContext(__METHOD__, null, 'foobar'),
            ],
            'level' => Logger::DEBUG,
            'level_name' => 'DEBUG',
            'channel' => '',
            'datetime' => new \DateTimeImmutable(),
            'extra' => [],
        ];

        $record = $processor($data);

        /** @var ContaoContext $context */
        $context = $record['extra']['contao'];

        $this->assertSame('foobar', $context->getUsername());

        $data = [
            'message' => '',
            'context' => [
                'contao' => new ContaoContext(__METHOD__),
            ],
            'level' => Logger::DEBUG,
            'level_name' => 'DEBUG',
            'channel' => '',
            'datetime' => new \DateTimeImmutable(),
            'extra' => [],
        ];

        $record = $processor($data);

        /** @var ContaoContext $context */
        $context = $record['extra']['contao'];

        $this->assertSame('k.jones', $context->getUsername());

        $tokenStorage->setToken(null);

        $data = [
            'message' => '',
            'context' => [
                'contao' => new ContaoContext(__METHOD__),
            ],
            'level' => Logger::DEBUG,
            'level_name' => 'DEBUG',
            'channel' => '',
            'datetime' => new \DateTimeImmutable(),
            'extra' => [],
        ];

        $record = $processor($data);

        /** @var ContaoContext $context */
        $context = $record['extra']['contao'];

        $this->assertSame('N/A', $context->getUsername());
    }

    /**
     * @dataProvider sourceProvider
     */
    public function testAddsTheSource(string|null $scope, string|null $contextSource, string $expectedSource): void
    {
        $requestStack = new RequestStack();

        if (null !== $scope) {
            $request = new Request();
            $request->attributes->set('_scope', $scope);

            $requestStack->push($request);
        }

        $data = [
            'message' => '',
            'context' => [
                'contao' => new ContaoContext(__METHOD__, null, null, null, null, $contextSource),
            ],
            'level' => Logger::DEBUG,
            'level_name' => 'DEBUG',
            'channel' => '',
            'datetime' => new \DateTimeImmutable(),
            'extra' => [],
        ];

        $processor = $this->getContaoTableProcessor($requestStack);
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

    /**
     * @dataProvider requestProvider
     */
    public function testAddsTheRequestUri(Request $request = null, string $uri = null): void
    {
        $requestStack = new RequestStack();

        if ($request) {
            $requestStack->push($request);
        }

        $data = [
            'context' => [
                'contao' => new ContaoContext(__METHOD__, null, null, null, 'foobar'),
            ],
            'level' => Logger::DEBUG,
            'level_name' => 'DEBUG',
            'channel' => '',
            'extra' => [],
            'datetime' => new \DateTimeImmutable(),
            'message' => '',
        ];

        $processor = $this->getContaoTableProcessor($requestStack);
        $record = $processor($data);

        /** @var ContaoContext $context */
        $context = $record['extra']['contao'];

        $this->assertSame($uri, $context->getUri());
    }

    public function requestProvider(): \Generator
    {
        yield 'regular URL' => [
            Request::create('https://www.contao.org/foo?bar=baz'),
            'https://www.contao.org/foo?bar=baz',
        ];

        yield 'encoded URL' => [
            Request::create('https://www.contao.org/foo?bar=baz&foo=b%20r'),
            'https://www.contao.org/foo?bar=baz&foo=b%20r',
        ];

        yield 'no request' => [null, null];
    }

    /**
     * @dataProvider requestWithPageIdProvider
     */
    public function testAddsThePageId(Request $request = null, int $pageId = null): void
    {
        $requestStack = new RequestStack();

        if ($request) {
            $requestStack->push($request);
        }

        $data = [
            'context' => [
                'contao' => new ContaoContext(__METHOD__, null, null, null, 'foobar'),
            ],
            'level' => Logger::DEBUG,
            'level_name' => 'DEBUG',
            'channel' => '',
            'extra' => [],
            'datetime' => new \DateTimeImmutable(),
            'message' => '',
        ];

        $processor = $this->getContaoTableProcessor($requestStack);
        $record = $processor($data);

        /** @var ContaoContext $context */
        $context = $record['extra']['contao'];

        $this->assertSame($pageId, $context->getPageId());
    }

    public function requestWithPageIdProvider(): \Generator
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class, ['id' => 13]);

        yield 'request with page model ID' => [new Request([], [], ['pageModel' => '42']), 42];
        yield 'request with page model' => [new Request([], [], ['pageModel' => $pageModel]), 13];
        yield 'request without page model' => [new Request(), null];
        yield 'no request' => [null, null];
    }

    private function getContaoTableProcessor(RequestStack $requestStack = null, TokenStorageInterface $tokenStorage = null): ContaoTableProcessor
    {
        $requestStack ??= $this->createMock(RequestStack::class);
        $tokenStorage ??= $this->createMock(TokenStorageInterface::class);

        return new ContaoTableProcessor($requestStack, $tokenStorage, $this->mockScopeMatcher());
    }
}
