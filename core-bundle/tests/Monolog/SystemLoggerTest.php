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
use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class SystemLoggerTest extends TestCase
{
    /**
     * @dataProvider psrLogActionsProvider
     */
    public function testSetsContaoContextForPsrLogActions(string $method): void
    {
        $message = 'Log message';
        $action = 'foo';

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method($method)
            ->with($message, $this->assertExpectedContaoContext(new ContaoContext(__METHOD__, $action)))
        ;

        (new SystemLogger($logger, $action))->{$method}($message);
    }

    public function psrLogActionsProvider(): array
    {
        return [
            ['emergency'],
            ['alert'],
            ['critical'],
            ['error'],
            ['warning'],
            ['notice'],
            ['info'],
            ['debug'],
        ];
    }

    public function testSetsContaoContextForLog(): void
    {
        $message = 'Log message';
        $action = 'foo';
        $level = LogLevel::INFO;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('log')
            ->with(
                $level,
                $message,
                $this->assertExpectedContaoContext(new ContaoContext(__METHOD__, $action))
            )
        ;

        (new SystemLogger($logger, $action))->log($level, $message);
    }

    /**
     * @return Callback<array>
     */
    private function assertExpectedContaoContext(ContaoContext $expectedContext): Callback
    {
        return $this->callback(
            function (array $context) use ($expectedContext) {
                $contaoContext = $context['contao'] ?? null;

                $this->assertInstanceOf(ContaoContext::class, $contaoContext);
                $this->assertSame($expectedContext->getAction(), $contaoContext->getAction());
                $this->assertSame($expectedContext->getFunc(), $contaoContext->getFunc());
                $this->assertSame($expectedContext->getUsername(), $contaoContext->getUsername());

                return true;
            }
        );
    }
}
