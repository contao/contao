<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Dca;

use Contao\ArrayUtil;
use Contao\CoreBundle\Dca\Observer\ChildDataObserver;
use Contao\CoreBundle\Dca\Observer\DataObserverInterface;
use Contao\CoreBundle\Dca\Observer\RootDataUpdater;

/**
 * @internal
 */
final class Data
{
    /**
     * @var \SplObjectStorage<DataObserverInterface, mixed>
     */
    private readonly \SplObjectStorage $readObservers;

    /**
     * @var \SplObjectStorage<DataObserverInterface, mixed>
     */
    private readonly \SplObjectStorage $writeObservers;

    private Data|null $root = null;

    public function __construct(
        private array $data = [],
        private readonly string $path = '',
    ) {
        $this->readObservers = new \SplObjectStorage();
        $this->writeObservers = new \SplObjectStorage();
    }

    public function all(): array
    {
        $this->notifyReadObservers();

        return $this->data;
    }

    public function get(string $path): mixed
    {
        $this->notifyReadObservers();

        return ArrayUtil::get($this->data, $path);
    }

    public function isEqualTo(mixed $data): bool
    {
        return $this->data === $data;
    }

    public function getData(string $path, array|null $fallback = null): self
    {
        $this->notifyReadObservers();

        $part = $this->get($path) ?? $fallback;

        if (!\is_array($part)) {
            throw new \LogicException(sprintf('Data at path "%s" has to be an array.', $path));
        }

        $data = new self($part, ($this->getPath() ? $this->getPath().'.' : '').$path);
        $data->setRoot($this->getRoot());

        $data->getReadObservers()->addAll($this->getReadObservers());
        $this->getRoot()->attachWriteObsever(new ChildDataObserver($data));

        if (!$this->isRoot()) {
            $data->attachWriteObsever(new RootDataUpdater());
        }

        return $data;
    }

    public function replace(array $data): void
    {
        $this->data = $data;

        $this->notifyWriteObservers();
    }

    public function set(string $key, mixed $value): self
    {
        $this->data = ArrayUtil::set($this->data, $key, $value);
        $this->notifyWriteObservers();

        return $this;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setRoot(self|null $root): void
    {
        $this->root = $root;
    }

    public function getRoot(): self
    {
        return $this->root ?? $this;
    }

    public function isRoot(): bool
    {
        return '' === $this->getPath();
    }

    public function attachReadObserver(DataObserverInterface $observer): void
    {
        $this->readObservers->attach($observer);
    }

    public function detachReadObserver(DataObserverInterface $observer): void
    {
        $this->readObservers->detach($observer);
    }

    /**
     * @return \SplObjectStorage<DataObserverInterface, mixed>
     */
    public function getReadObservers(): \SplObjectStorage
    {
        return $this->readObservers;
    }

    public function attachWriteObsever(DataObserverInterface $observer): void
    {
        $this->writeObservers->attach($observer);
    }

    public function detachWriteObserver(DataObserverInterface $observer): void
    {
        $this->writeObservers->detach($observer);
    }

    /**
     * @return \SplObjectStorage<DataObserverInterface, mixed>
     */
    public function getWriteObservers(): \SplObjectStorage
    {
        return $this->writeObservers;
    }

    private function notifyReadObservers(): void
    {
        foreach ($this->readObservers as $observer) {
            $observer->update($this);
        }
    }

    private function notifyWriteObservers(): void
    {
        foreach ($this->writeObservers as $observer) {
            $observer->update($this);
        }
    }
}
