<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Session\Attribute;

use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;

/**
 * Provides an array access adapter for a session attribute bag.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ArrayAttributeBag extends AttributeBag implements \ArrayAccess
{
    /**
     * {@inheritdoc}
     */
    public function offsetExists($key)
    {
        return $this->has($key);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($key)
    {
        $this->remove($key);
    }

    /**
     * Adds an alias for $this->all().
     *
     * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
     *             Use the all() method instead.
     */
    public function getData()
    {
        trigger_error(
            'Using Session::getData() has been deprecated and will no longer work in Contao 5.0. '
                . 'Use the all() method instead.',
            E_USER_DEPRECATED
        );

        return $this->all();
    }

    /**
     * Adds an alias for $this->replace().
     *
     * @param array $attributes The attributes
     *
     * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
     *             Use the replace() method instead.
     */
    public function setData(array $attributes)
    {
        trigger_error(
            'Using Session::setData() has been deprecated and will no longer work in Contao 5.0. '
                . 'Use the replace() method instead.',
            E_USER_DEPRECATED
        );

        $this->replace($attributes);
    }
}
