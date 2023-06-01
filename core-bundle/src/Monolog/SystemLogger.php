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

final class SystemLogger implements LoggerInterface
{
    public function __construct(
        private LoggerInterface $inner,
        private string $action,
    ) {
    }

    public function emergency($message, array $context = []): void
    {
        $this->inner->emergency($message, $this->addContaoContext($context));
    }

    public function alert($message, array $context = []): void
    {
        $this->inner->alert($message, $this->addContaoContext($context));
    }

    public function critical($message, array $context = []): void
    {
        $this->inner->critical($message, $this->addContaoContext($context));
    }

    public function error($message, array $context = []): void
    {
        $this->inner->error($message, $this->addContaoContext($context));
    }

    public function warning($message, array $context = []): void
    {
        $this->inner->warning($message, $this->addContaoContext($context));
    }

    public function notice($message, array $context = []): void
    {
        $this->inner->notice($message, $this->addContaoContext($context));
    }

    public function info($message, array $context = []): void
    {
        $this->inner->info($message, $this->addContaoContext($context));
    }

    public function debug($message, array $context = []): void
    {
        $this->inner->debug($message, $this->addContaoContext($context));
    }

    public function log($level, $message, array $context = []): void
    {
        $this->inner->log($level, $message, $this->addContaoContext($context));
    }

    private function addContaoContext(array $context): array
    {
        if (isset($context['contao'])) {
            return $context;
        }

        $context['contao'] = new ContaoContext($this->getCallerMethod(), $this->action);

        return $context;
    }

    private function getCallerMethod(): string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 4);
        $caller = $trace[3] ?? null;

        return $caller ? $caller['class'].'::'.$caller['function'] : '';
    }
}
