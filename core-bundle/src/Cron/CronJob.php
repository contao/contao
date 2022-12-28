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
    private string $name;

    public function __construct(private object $service, private string $interval, private string|null $method = null)
    {
        $this->name = $service::class;

        if (!\is_callable($service)) {
            if (null === $this->method) {
                throw new \InvalidArgumentException('Service must be a callable when no method name is defined');
            }

            $this->name .= '::'.$method;
        }
    }

    public function __invoke(string $scope): PromiseInterface|null
    {
        if (\is_callable($this->service)) {
            return ($this->service)($scope);
        }

        return $this->service->{$this->method}($scope);
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
}
