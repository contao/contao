<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener;

use Contao\CoreBundle\EventListener\CspReportListener;
use Contao\CoreBundle\Monolog\ContaoContext;
use Nelmio\SecurityBundle\ContentSecurityPolicy\Violation\Report;
use Nelmio\SecurityBundle\ContentSecurityPolicy\Violation\ReportEvent;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CspReportListenerTest extends TestCase
{
    public function testLogsCspReportWithContaoContext(): void
    {
        $uri = 'https://example.com/foobar';
        $line = 1337;
        $directive = 'script-src-elem';
        $expectedMessage = sprintf('Content-Security-Policy violation reported for "%s" on line %d', $directive, $line);

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('error')
            ->with(
                $expectedMessage,
                $this->callback(
                    function (array $context) use ($uri): bool {
                        $this->assertInstanceOf(ContaoContext::class, $context['contao'] ?? null);

                        return $context['contao']->getUri() === $uri;
                    },
                ),
            )
        ;

        $report = new Report([
            'source-file' => $uri,
            'line-number' => $line,
            'effective-directive' => $directive,
        ]);

        $event = new ReportEvent($report);

        (new CspReportListener($logger))($event);
    }
}
