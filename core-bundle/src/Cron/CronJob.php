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

final class CronJob
{
    /**
     * @var object
     */
    private $service;

    /**
     * @var string
     */
    private $method;

    /**
     * @var string
     */
    private $interval;

    /**
     * @var string
     */
    private $name;

    public function __construct(object $service, string $method, string $interval)
    {
        $this->service = $service;
        $this->method = $method;
        $this->interval = $interval;
        $this->name = \get_class($service);

        if (!\is_callable($service)) {
            $this->name .= '::'.$method;
        }
    }

    public function __invoke(): void
    {
        if (\is_callable($this->service)) {
            ($this->service)();
        } else {
            $this->service->{$this->method}();
        }
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

    public function setScope(string $scope): self
    {
        if ($this->service instanceof ScopedCronJobInterface) {
            $this->service->setScope($scope);
        }

        return $this;
    }
}
