<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Dca\Schema;

use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\CoreBundle\Framework\FrameworkAwareTrait;
use Contao\System;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * @template CallbackInput of mixed
 * @template CallbackResult of mixed
 */
class Callback extends Schema implements ContainerAwareInterface, FrameworkAwareInterface, ValidatingSchemaInterface
{
    use ContainerAwareTrait;
    use FrameworkAwareTrait;

    protected bool $allowServiceCalls = true;

    // TODO: Set assureCallable from the configuration array when creating the callback.
    protected bool $assureCallable = true;

    public function __invoke(mixed ...$arguments): mixed
    {
        return $this->call(...$arguments);
    }

    /**
     * @param CallbackInput ...$arguments
     *
     * @return CallbackResult
     */
    public function call(...$arguments)
    {
        $callback = $this->getCallback();

        if (\is_array($callback)) {
            if (!$this->framework) {
                throw new \LogicException(sprintf('You must set the framework service via "Callback::setFramework()" before calling the callback %s:%s', $callback[0], $callback[1]));
            }

            return $this->framework->getAdapter(System::class)->importStatic($callback[0])->{$callback[1]}(...$arguments);
        }

        if (!\is_callable($callback)) {
            throw new \LogicException('This callback is not callable.');
        }

        return $callback(...$arguments);
    }

    public function allowsServiceCalls(): bool
    {
        return $this->allowServiceCalls;
    }

    public function validate(): void
    {
        $data = $this->all();

        if ($this->isClosureCallback($data)) {
            return;
        }

        if ($this->allowsServiceCalls()) {
            if (null === $this->container) {
                throw new \LogicException('You must set the service container via "Callback::setContainer()" or disallow service calls for this callback.');
            }

            if (($data[0] ?? false) && $this->container->has($data[0])) {
                return;
            }
        }

        if (!\is_callable($data)) {
            throw new \InvalidArgumentException(sprintf('Callback %s is not callable', implode('::', $data)));
        }
    }

    /**
     * @return array|\Closure
     */
    private function getCallback()
    {
        /** @var array<\Closure> $data */
        $data = $this->all();

        if ($this->assureCallable && empty($data)) {
            return static function (): void {};
        }

        return $this->isClosureCallback($data) ? $data[0] : $data;
    }

    private function isClosureCallback(array $data): bool
    {
        return 1 === \count($data) && $data[0] instanceof \Closure;
    }
}
