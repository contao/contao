<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Cron;

use GuzzleHttp\Promise\PromiseInterface;

class CronJob
{
    private readonly string $name;

    private \DateTimeInterface $previousRun;

    public function __construct(
        private readonly object $service,
        private readonly string $interval,
        private readonly string|null $method = null,
        string|null $name = null,
    ) {
        $name ??= $service::class;

        if (!\is_callable($service)) {
            if (null === $this->method) {
                throw new \InvalidArgumentException('Service must be a callable when no method name is defined');
            }

            $name .= '::'.$method;
        }

        if (preg_match('/[^\x00-\x7F]/', $name)) {
            trigger_deprecation('contao/core-bundle', '5.3', 'Using non-ASCII characters in cron job names is deprecated and will no longer work in Contao 7.');

            $name = preg_replace_callback('/[^\x00-\x7F]+/', static fn (array $match): string => bin2hex($match[0]), $name);
        }

        $this->name = $name;
    }

    public function __invoke(string $scope): PromiseInterface|null
    {
        if (\is_callable($this->service)) {
            $result = ($this->service)($scope);
        } else {
            $result = $this->service->{$this->method}($scope);
        }

        if (null === $result || $result instanceof PromiseInterface) {
            return $result;
        }

        throw new \UnexpectedValueException(\sprintf('Invalid return value from "%s": expected null or PromiseInterface, got %s', $this->name, get_debug_type($result)));
    }

    public function getService(): object
    {
        return $this->service;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getInterval(): string
    {
        return $this->interval;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setPreviousRun(\DateTimeInterface $previousRun): self
    {
        $this->previousRun = $previousRun;

        return $this;
    }

    public function getPreviousRun(): \DateTimeInterface
    {
        return $this->previousRun;
    }
}
