<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Compat;

final class InvokableValueHolder implements SafeHTMLValueHolderInterface
{
    /**
     * @var callable
     */
    private $callable;

    /**
     * @var string
     */
    private $name;

    /**
     * @internal
     */
    public function __construct(callable $callable, string $name)
    {
        $this->callable = $callable;
        $this->name = $name;
    }

    public function __toString(): string
    {
        return (string) $this->invoke();
    }

    public function invoke(...$args)
    {
        try {
            return ProxyFactory::createValueHolder(($this->callable)(...$args), $this->name);
        } catch (\Throwable $e) {
            throw new \RuntimeException("Value '{$this->name}' could not be invoked: {$e->getMessage()}", 0, $e);
        }
    }
}
