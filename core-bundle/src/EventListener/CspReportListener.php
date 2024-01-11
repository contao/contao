<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\Monolog\ContaoContext;
use Nelmio\SecurityBundle\ContentSecurityPolicy\Violation\ReportEvent;
use Psr\Log\LoggerInterface;

/**
 * Adds a system log entry for a CSP report.
 *
 * @internal
 */
class CspReportListener
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function __invoke(ReportEvent $event): void
    {
        $report = $event->getReport();

        $context = new ContaoContext(
            __METHOD__,
            ContaoContext::ERROR,
            browser: $report->getUserAgent(),
            uri: $report->getSourceFile(),
        );

        $msg = sprintf('Content-Security-Policy violation reported for "%s"', $report->getDirective());

        if (null !== ($line = ($report->getData()['line-number'] ?? null))) {
            $msg .= ' on line '.$line;
        }

        $this->logger->error($msg, ['contao' => $context]);
    }
}
