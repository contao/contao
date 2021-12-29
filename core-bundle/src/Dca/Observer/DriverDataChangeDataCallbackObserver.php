<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Dca\Observer;

use Contao\CoreBundle\Dca\Data;
use Contao\CoreBundle\Dca\Driver\MutableDataDriverInterface;

class DriverDataChangeDataCallbackObserver implements DataCallbackObserverInterface
{
    /**
     * @var callable|null
     */
    private $callback;

    public function __construct(
        private readonly string $name,
        private readonly MutableDataDriverInterface $driver,
    ) {
    }

    public function setCallback(callable|null $callback): self
    {
        $this->callback = $callback;

        return $this;
    }

    public function runCallback(Data $subject): void
    {
        ($this->callback)($subject);
    }

    public function getResourceName(): string
    {
        return $this->name;
    }

    public function getDriver(): MutableDataDriverInterface
    {
        return $this->driver;
    }

    public function update(Data $subject): void
    {
        if (\is_callable($this->callback) && $this->getDriver()->hasChanged($this->getResourceName())) {
            $this->runCallback($subject->getRoot());
        }
    }
}
