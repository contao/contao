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

final class ArrayValueHolder extends \ArrayIterator implements SafeHTMLValueHolderInterface
{
    /**
     * @var string
     */
    private $valueName;

    /**
     * @internal
     */
    public function __construct(array $array, string $name)
    {
        parent::__construct($array);

        $this->valueName = $name;
    }

    public function __toString(): string
    {
        throw new \RuntimeException("Value '{$this->valueName}' is an array and but was tried to be output as a string.");
    }

    /**
     * @return mixed
     */
    public function offsetGet($key)
    {
        return ProxyFactory::createValueHolder(
            parent::offsetGet($key),
            "{$this->valueName}.$key"
        );
    }

    /**
     * @return mixed
     */
    public function current()
    {
        return ProxyFactory::createValueHolder(
            parent::current(),
            "{$this->valueName}.{$this->key()}"
        );
    }
}
