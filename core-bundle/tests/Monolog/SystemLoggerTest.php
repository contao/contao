<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Monolog;

use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\Monolog\SystemLogger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SystemLoggerTest extends TestCase
{
    /**
     * @dataProvider psrLogActionsProvider
     */
    public function testSetsContaoContextForPsrLogActions(string $method): void
    {
        $message = 'Log message';
        $logger = $this->createMock(LoggerInterface::class);
        $action = 'foo';

        $expectedContext = new ContaoContext(__METHOD__, $action);

        $logger
            ->expects($this->once())
            ->method($method)
            ->with(
                $message,
                $this->callback(
                    function (array $context) use ($expectedContext) {
                        /** @var ContaoContext $contaoContext */
                        $contaoContext = $context['contao'] ?? null;

                        $this->assertInstanceOf(ContaoContext::class, $contaoContext);
                        $this->assertSame($expectedContext->getAction(), $contaoContext->getAction());
                        $this->assertSame($expectedContext->getFunc(), $contaoContext->getFunc());
                        $this->assertSame($expectedContext->getUsername(), $contaoContext->getUsername());

                        return true;
                    }
                )
            )
        ;

        (new SystemLogger($logger, $action))
            ->{$method}($message)
        ;
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
}
