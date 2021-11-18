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
    public function testSetsContaoContextForInfo(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $expectedContext = new ContaoContext(__METHOD__, ContaoContext::GENERAL);
        $this->assertLogActionWithContaoContext($logger, LogLevel::INFO, 'Generic log message', $expectedContext);

        (new SystemLogger($logger))->info('Generic log message');
    }

    public function testSetsContaoContextForError(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $expectedContext = new ContaoContext(__METHOD__, ContaoContext::ERROR);
        $this->assertLogActionWithContaoContext($logger, LogLevel::ERROR, 'Error message', $expectedContext);

        (new SystemLogger($logger))->error('Error message');
    }

    public function testSetsContaoContextForLogWithCustomAction(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $expectedContext = new ContaoContext(__METHOD__, ContaoContext::CRON);
        $this->assertLogActionWithContaoContext($logger, LogLevel::INFO, 'Message with custom action CRON', $expectedContext);

        (new SystemLogger($logger))->log(ContaoContext::CRON, 'Message with custom action CRON');
    }

    public function testAllowsCustomMethodNameForInfo(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $expectedContext = new ContaoContext('MyClass::myMethod', ContaoContext::GENERAL);
        $this->assertLogActionWithContaoContext($logger, LogLevel::INFO, 'Generic log message', $expectedContext);

        (new SystemLogger($logger))->info('Generic log message', 'MyClass::myMethod');
    }

    public function testAllowsCustomMethodNameForError(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $expectedContext = new ContaoContext('MyClass::myMethod', ContaoContext::ERROR);
        $this->assertLogActionWithContaoContext($logger, LogLevel::ERROR, 'Error message', $expectedContext);

        (new SystemLogger($logger))->error('Error message', 'MyClass::myMethod');
    }

    public function testAllowsCustomMethodNameForLog(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        $expectedContext = new ContaoContext('MyClass::myMethod', ContaoContext::CRON);
        $this->assertLogActionWithContaoContext($logger, LogLevel::INFO, 'Message with custom action CRON', $expectedContext);

        (new SystemLogger($logger))->log(ContaoContext::CRON, 'Message with custom action CRON', 'MyClass::myMethod');
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
