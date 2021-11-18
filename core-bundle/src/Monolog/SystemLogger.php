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

    public function access(string $message, string $method = null): void
    {
        $this->log(ContaoContext::ACCESS, $message, $method ?? $this->getCallerMethod());
    }

    public function configuration(string $message, string $method = null): void
    {
        $this->log(ContaoContext::CONFIGURATION, $message, $method ?? $this->getCallerMethod());
    }

    public function cron(string $message, string $method = null): void
    {
        $this->log(ContaoContext::CRON, $message, $method ?? $this->getCallerMethod());
    }

    public function email(string $message, string $method = null): void
    {
        $this->log(ContaoContext::EMAIL, $message, $method ?? $this->getCallerMethod());
    }

    public function error(string $message, string $method = null): void
    {
        $this->log(ContaoContext::ERROR, $message, $method ?? $this->getCallerMethod(), LogLevel::ERROR);
    }

    public function files(string $message, string $method = null): void
    {
        $this->log(ContaoContext::FILES, $message, $method ?? $this->getCallerMethod());
    }

    public function forms(string $message, string $method = null): void
    {
        $this->log(ContaoContext::FORMS, $message, $method ?? $this->getCallerMethod());
    }

    public function general(string $message, string $method = null): void
    {
        $this->info($message, $method ?? $this->getCallerMethod());
    }

    public function info(string $message, string $method = null): void
    {
        $this->log(ContaoContext::GENERAL, $message, $method ?? $this->getCallerMethod());
    }

    public function log(string $action, string $message, string $method = null, string $level = null): void
    {
        $this->logWithContaoContext($level ?? LogLevel::INFO, $message, $action, $method ?? $this->getCallerMethod());
    }

    private function logWithContaoContext(string $level, string $message, string $action, string $method): void
    {
        $this->logger->log($level, $message, ['contao' => new ContaoContext($method, $action)]);
    }

    private function getCallerMethod(): string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $caller = $trace[2] ?? null;

        return $caller ? $caller['class'].'::'.$caller['function'] : '';
    }
}
