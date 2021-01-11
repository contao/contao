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

final class ObjectValueHolder implements SafeHTMLValueHolderInterface
{
    /**
     * @var object
     */
    private $object;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $class;

    /**
     * @var array
     */
    private static $classCache = [];

    /**
     * @internal
     */
    public function __construct(object $object, string $name)
    {
        $this->object = $object;
        $this->name = $name;
        $this->class = \get_class($this->object);
    }

    public function __call($item, $arguments)
    {
        // Properties
        if (isset($this->object->$item) || \array_key_exists((string) $item, (array) $this->object)) {
            return ProxyFactory::createValueHolder($this->object->$item, "{$this->name}.$item");
        }

        // Methods
        $mapping = $this->getClassMapping();
        $method = $mapping[$item] ?? null;

        if (null === $method) {
            throw new \RuntimeException("Value '{$this->name}' of type {$this->class} does not support accessing {$item}.");
        }

        return ProxyFactory::createValueHolder($this->object->$method(...$arguments), "{$this->name}#$method");
    }

    public function __toString(): string
    {
        if (!method_exists($this->object, '__toString')) {
            throw new \RuntimeException("Value '{$this->name}' of type {$this->class} cannot be converted to string.");
        }

        return (string) $this->object;
    }

    /**
     * Creates a mapping of virtual accessors to real methods
     * (e.g. "allowed" -> "isAllowed").
     *
     * This is effectively a clone of @see twig_get_attribute in order to
     * support the same access model while still being able to wrap the values.
     */
    private function getClassMapping(): array
    {
        if (null !== ($cache = self::$classCache[$this->class] ?? null)) {
            return $cache;
        }

        $methods = get_class_methods($this->object);
        sort($methods);

        $lcMethods = array_map(
            static function ($value) {
                return strtr($value, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz');
            },
            $methods
        );

        $classCache = [];

        foreach ($methods as $i => $method) {
            $classCache[$method] = $method;
            $classCache[$lcName = $lcMethods[$i]] = $method;

            if ('g' === $lcName[0] && 0 === strpos($lcName, 'get')) {
                $name = substr($method, 3);
                $lcName = substr($lcName, 3);
            } elseif ('i' === $lcName[0] && 0 === strpos($lcName, 'is')) {
                $name = substr($method, 2);
                $lcName = substr($lcName, 2);
            } elseif ('h' === $lcName[0] && 0 === strpos($lcName, 'has')) {
                $name = substr($method, 3);
                $lcName = substr($lcName, 3);

                if (\in_array('is'.$lcName, $lcMethods, true)) {
                    continue;
                }
            } else {
                continue;
            }

            if ($name) {
                if (!isset($classCache[$name])) {
                    $classCache[$name] = $method;
                }

                if (!isset($classCache[$lcName])) {
                    $classCache[$lcName] = $method;
                }
            }
        }

        return self::$classCache[$this->class] = $classCache;
    }
}
