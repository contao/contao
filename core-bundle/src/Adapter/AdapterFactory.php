<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Adapter;

/**
 * A factory that creates instances of any class or fetches an Adapter class
 * of them. This allows you to inject the AdapterFactory service into your new
 * code and make this one testable as you can mock any call on createInstance()
 * or getAdapter(). Note that this is a way to make your new code that
 * relies on untestable (e.g. classes using static methods) testable. It should
 * however never be required for new components you write. Use proper Dependency
 * Injection and architecture there.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 *
 * @internal
 */
class AdapterFactory implements AdapterFactoryInterface
{
    /**
     * Adapter class cache
     * @var array
     */
    private $adapterCache = [];

    /**
     * Creates a new instance of a given class.
     *
     * @param string $class Fully qualified class name.
     * @param array $args Constructor arguments.
     *
     * @return mixed
     */
    public function createInstance($class, $args = [])
    {
        $method = '__construct';

        if (in_array('getInstance', get_class_methods($class))) {

            $method = 'getInstance';
        }

        return call_user_func_array(array($class, $method), $args);
    }

    /**
     * Returns an adapter class for a given class.
     *
     * @param string $class Fully qualified class name.
     * @param array $args Constructor arguments.
     *
     * @return mixed
     */
    public function getAdapter($class, $args = [])
    {
        $cacheKey = md5($class . ';' . json_encode($args));

        if (isset($this->adapterCache[$cacheKey])) {

            return $this->adapterCache[$cacheKey];
        }

        $this->adapterCache[$cacheKey] = new Adapter($class);

        return $this->adapterCache[$cacheKey];
    }
}
