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

use Psr\Log\LogLevel;

/**
 * The Contao logger supplies a fluid interface to add Contao specific context information
 * to a log message. This makes it easier to create log messages with a context like
 * ContaoContext::CRON or ContaoContext::FILES.
 */
final class Logger extends ContextAwareLogger implements LoggerInterface
{
    public function asContaoAccess(): self
    {
        return $this->addContext('contao', new ContaoContext($this->getCallerMethod(), ContaoContext::ACCESS));
    }

    public function asContaoConfiguration(): self
    {
        return $this->addContext('contao', new ContaoContext($this->getCallerMethod(), ContaoContext::CONFIGURATION));
    }

    public function asContaoCron(): self
    {
        return $this->addContext('contao', new ContaoContext($this->getCallerMethod(), ContaoContext::CRON));
    }

    public function asContaoEmail(): self
    {
        return $this->addContext('contao', new ContaoContext($this->getCallerMethod(), ContaoContext::EMAIL));
    }

    public function asContaoError(): self
    {
        return $this->addContext('contao', new ContaoContext($this->getCallerMethod(), ContaoContext::ERROR));
    }

    public function asContaoFiles(): self
    {
        return $this->addContext('contao', new ContaoContext($this->getCallerMethod(), ContaoContext::FILES));
    }

    public function asContaoForms(): self
    {
        return $this->addContext('contao', new ContaoContext($this->getCallerMethod(), ContaoContext::FORMS));
    }

    public function asContaoGeneral(): self
    {
        return $this->addContext('contao', new ContaoContext($this->getCallerMethod(), ContaoContext::GENERAL));
    }

    public function withContaoContext(ContaoContext $context = null): self
    {
        return $this->addContext('contao', $context ?? new ContaoContext($this->getCallerMethod()));
    }

    public function withContaoAction(string $action): self
    {
        $context = $this->getContaoContext();
        $context->setAction($action);

        return $this->withContaoContext($context);
    }

    public function withContaoFunc(string $func): self
    {
        $context = $this->getContaoContext();
        $context->setFunc($func);

        return $this->withContaoContext($context);
    }

    public function withContaoUsername(string $username): self
    {
        $context = $this->getContaoContext();
        $context->setUsername($username);

        return $this->withContaoContext($context);
    }

    public function logAccess(string $message, string $method = null, string $username = null): void
    {
        $this->logWithContaoContext(LogLevel::INFO, $message, ContaoContext::ACCESS, $method ?? $this->getCallerMethod(), $username);
    }

    public function logConfiguration(string $message, string $method = null, string $username = null): void
    {
        $this->logWithContaoContext(LogLevel::INFO, $message, ContaoContext::CONFIGURATION, $method ?? $this->getCallerMethod(), $username);
    }

    public function logCron(string $message, string $method = null, string $username = null): void
    {
        $this->logWithContaoContext(LogLevel::INFO, $message, ContaoContext::CRON, $method ?? $this->getCallerMethod(), $username);
    }

    public function logEmail(string $message, string $method = null, string $username = null): void
    {
        $this->logWithContaoContext(LogLevel::INFO, $message, ContaoContext::EMAIL, $method ?? $this->getCallerMethod(), $username);
    }

    public function logError(string $message, string $method = null, string $username = null): void
    {
        $this->logWithContaoContext(LogLevel::ERROR, $message, ContaoContext::ERROR, $method ?? $this->getCallerMethod(), $username);
    }

    public function logFiles(string $message, string $method = null, string $username = null): void
    {
        $this->logWithContaoContext(LogLevel::INFO, $message, ContaoContext::FILES, $method ?? $this->getCallerMethod(), $username);
    }

    public function logForms(string $message, string $method = null, string $username = null): void
    {
        $this->logWithContaoContext(LogLevel::INFO, $message, ContaoContext::FORMS, $method ?? $this->getCallerMethod(), $username);
    }

    public function logGeneral(string $message, string $method = null, string $username = null): void
    {
        $this->logWithContaoContext(LogLevel::INFO, $message, ContaoContext::GENERAL, $method ?? $this->getCallerMethod(), $username);
    }

    public function logActionName(string $action, string $message, string $method = null, string $username = null): void
    {
        $this->logWithContaoContext(LogLevel::INFO, $message, $action, $method ?? $this->getCallerMethod(), $username);
    }

    public function logWithContaoContext(string $level, string $message, string $action, string $method, string $username = null): void
    {
        $this->logger->log($level, $message, ['contao' => new ContaoContext($method, $action, $username)]);
    }

    private function getContaoContext(): ContaoContext
    {
        return $this->getContextByName('contao') ?? new ContaoContext($this->getCallerMethod(3));
    }

    private function getCallerMethod(int $depth = 2): string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $depth + 1);
        $caller = $trace[$depth] ?? null;

        return $caller ? $caller['class'].'::'.$caller['function'] : '';
    }
}
