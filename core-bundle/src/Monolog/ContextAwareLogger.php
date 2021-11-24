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

class ContextAwareLogger implements ContextAwareLoggerInterface
{
    protected LoggerInterface $logger;

    private array $context;

    public function __construct(LoggerInterface $logger, array $context = [])
    {
        $this->logger = $logger;
        $this->context = $context;
    }

    /**
     * @return static
     */
    public function addContext(string $key, $context): self
    {
        return $this->createWithContext(array_merge($this->context, [$key => $context]));
    }

    /**
     * @return static
     */
    public function withContext(array $context): self
    {
        return $this->createWithContext($context);
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getContextByName(string $name = null)
    {
        return $this->context[$name] ?? null;
    }

    public function emergency($message, array $context = []): void
    {
        $this->logger->emergency($message, array_merge($this->consumeContext(), $context));
    }

    public function alert($message, array $context = []): void
    {
        $this->logger->alert($message, array_merge($this->consumeContext(), $context));
    }

    public function critical($message, array $context = []): void
    {
        $this->logger->critical($message, array_merge($this->consumeContext(), $context));
    }

    public function error($message, array $context = []): void
    {
        $this->logger->error($message, array_merge($this->consumeContext(), $context));
    }

    public function warning($message, array $context = []): void
    {
        $this->logger->warning($message, array_merge($this->consumeContext(), $context));
    }

    public function notice($message, array $context = []): void
    {
        $this->logger->notice($message, array_merge($this->consumeContext(), $context));
    }

    public function info($message, array $context = []): void
    {
        $this->logger->info($message, array_merge($this->consumeContext(), $context));
    }

    public function debug($message, array $context = []): void
    {
        $this->logger->debug($message, array_merge($this->consumeContext(), $context));
    }

    public function log($level, $message, array $context = []): void
    {
        $this->logger->log($level, $message, array_merge($this->consumeContext(), $context));
    }

    protected function consumeContext(): array
    {
        $context = $this->context;
        $this->context = [];

        return $context;
    }

    /**
     * @return static
     */
    protected function createWithContext(array $context): self
    {
        $class = static::class;

        return new $class($this->logger, $context);
    }
}
