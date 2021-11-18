<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Monolog;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

final class SystemLogger
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function info(string $message, string $method = null): void
    {
        $this->logWithContaoContext(LogLevel::INFO, $message, ContaoContext::GENERAL, $method);
    }

    public function error(string $message, string $method = null): void
    {
        $this->logWithContaoContext(LogLevel::ERROR, $message, ContaoContext::ERROR, $method);
    }

    public function log(string $action, string $message, string $method = null): void
    {
        $this->logWithContaoContext(LogLevel::INFO, $message, $action, $method);
    }

    private function logWithContaoContext(string $level, string $message, string $action = ContaoContext::GENERAL, string $method = null): void
    {
        $method = $method ?? $this->getCallerMethod();

        $context = new ContaoContext($method, $action);

        $this->logger->log($level, $message, ['contao' => $context]);
    }

    private function getCallerMethod(): string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 4);
        $caller = $trace[3] ?? null;

        return $caller ? $caller['class'].'::'.$caller['function'] : '';
    }
}
