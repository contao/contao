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

use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\Monolog\SystemLogger;
use Contao\CoreBundle\Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class SystemLoggerTest extends TestCase
{
    /**
     * @dataProvider logMessageProvider
     */
    public function testSetsContaoContextForAccess(string $message, string $method = null): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $expectedContext = new ContaoContext($method ?? __METHOD__, ContaoContext::ACCESS);
        $this->assertLogActionWithContaoContext($logger, LogLevel::INFO, $message, $expectedContext);

        (new SystemLogger($logger))->access($message, $method);
    }

    /**
     * @dataProvider logMessageProvider
     */
    public function testSetsContaoContextForConfiguration(string $message, string $method = null): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $expectedContext = new ContaoContext($method ?? __METHOD__, ContaoContext::CONFIGURATION);
        $this->assertLogActionWithContaoContext($logger, LogLevel::INFO, $message, $expectedContext);

        (new SystemLogger($logger))->configuration($message, $method);
    }

    /**
     * @dataProvider logMessageProvider
     */
    public function testSetsContaoContextForCron(string $message, string $method = null): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $expectedContext = new ContaoContext($method ?? __METHOD__, ContaoContext::CRON);
        $this->assertLogActionWithContaoContext($logger, LogLevel::INFO, $message, $expectedContext);

        (new SystemLogger($logger))->cron($message, $method);
    }

    /**
     * @dataProvider logMessageProvider
     */
    public function testSetsContaoContextForEmail(string $message, string $method = null): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $expectedContext = new ContaoContext($method ?? __METHOD__, ContaoContext::EMAIL);
        $this->assertLogActionWithContaoContext($logger, LogLevel::INFO, $message, $expectedContext);

        (new SystemLogger($logger))->email($message, $method);
    }

    /**
     * @dataProvider logMessageProvider
     */
    public function testSetsContaoContextForError(string $message, string $method = null): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $expectedContext = new ContaoContext($method ?? __METHOD__, ContaoContext::ERROR);
        $this->assertLogActionWithContaoContext($logger, LogLevel::ERROR, $message, $expectedContext);

        (new SystemLogger($logger))->error($message, $method);
    }

    /**
     * @dataProvider logMessageProvider
     */
    public function testSetsContaoContextForFiles(string $message, string $method = null): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $expectedContext = new ContaoContext($method ?? __METHOD__, ContaoContext::FILES);
        $this->assertLogActionWithContaoContext($logger, LogLevel::INFO, $message, $expectedContext);

        (new SystemLogger($logger))->files($message, $method);
    }

    /**
     * @dataProvider logMessageProvider
     */
    public function testSetsContaoContextForForms(string $message, string $method = null): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $expectedContext = new ContaoContext($method ?? __METHOD__, ContaoContext::FORMS);
        $this->assertLogActionWithContaoContext($logger, LogLevel::INFO, $message, $expectedContext);

        (new SystemLogger($logger))->forms($message, $method);
    }

    /**
     * @dataProvider logMessageProvider
     */
    public function testSetsContaoContextForGeneral(string $message, string $method = null): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $expectedContext = new ContaoContext($method ?? __METHOD__, ContaoContext::GENERAL);
        $this->assertLogActionWithContaoContext($logger, LogLevel::INFO, $message, $expectedContext);

        (new SystemLogger($logger))->general($message, $method);
    }

    /**
     * @dataProvider logMessageProvider
     */
    public function testSetsContaoContextForInfo(string $message, string $method = null): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $expectedContext = new ContaoContext($method ?? __METHOD__, ContaoContext::GENERAL);
        $this->assertLogActionWithContaoContext($logger, LogLevel::INFO, $message, $expectedContext);

        (new SystemLogger($logger))->info($message, $method);
    }

    /**
     * @dataProvider logMessageWithActionProvider
     */
    public function testSetsContaoContextForLogWithCustomAction(string $action, string $message, string $method = null): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $expectedContext = new ContaoContext($method ?? __METHOD__, $action);
        $this->assertLogActionWithContaoContext($logger, LogLevel::INFO, $message, $expectedContext);

        (new SystemLogger($logger))->log($action, $message, $method);
    }

    public function logMessageProvider(): \Generator
    {
        yield 'without method' => ['Log message', null];
        yield 'with custom method' => ['Log message', 'MyClass::myMethod'];
    }

    public function logMessageWithActionProvider(): \Generator
    {
        yield 'action from ContaoContext' => [ContaoContext::ACCESS, 'Log message', null];
        yield 'custom action' => ['FooAction', 'Log message', null];
        yield 'custom action and custom method' => ['FooAction', 'Log message', 'MyClass::myMethod'];
    }

    private function assertLogActionWithContaoContext(MockObject $logger, string $level, string $message, ContaoContext $expectedContext): void
    {
        $logger
            ->expects($this->once())
            ->method('log')
            ->with(
                $level,
                $message,
                $this->callback(
                    function (array $context) use ($expectedContext) {
                        /** @var ContaoContext $contaoContext */
                        $contaoContext = $context['contao'] ?? null;

                        $this->assertInstanceOf(ContaoContext::class, $contaoContext);
                        $this->assertSame($expectedContext->getAction(), $contaoContext->getAction());
                        $this->assertSame($expectedContext->getFunc(), $contaoContext->getFunc());

                        return true;
                    }
                )
            )
            ->willReturn(null)
        ;
    }
}
