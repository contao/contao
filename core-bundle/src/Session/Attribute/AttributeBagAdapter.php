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
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

/**
 * Provides an array access adapter for a session attribute bag.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class AttributeBagAdapter implements AttributeBagInterface, \ArrayAccess
{
    /**
     * @var AttributeBag
     */
    private $targetBag;

    /**
     * Constructor.
     *
     * @param AttributeBag $targetBag The target bag
     */
    public function __construct(AttributeBag $targetBag)
    {
        $this->targetBag = $targetBag;
    }

    /**
     * {@inheritdoc}
     */
    public function has($name)
    {
        return $this->targetBag->has($name);
    }

    /**
     * {@inheritdoc}
     */
    public function get($name, $default = null)
    {
        return $this->targetBag->get($name, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function set($name, $value)
    {
        $this->targetBag->set($name, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function all()
    {
        return $this->targetBag->all();
    }

    /**
     * {@inheritdoc}
     */
    public function replace(array $attributes)
    {
        $this->targetBag->replace($attributes);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($name)
    {
        $this->targetBag->remove($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->targetBag->getName();
    }

    /**
     * Sets the bag's name.
     *
     * @param string $name The name
     */
    public function setName($name)
    {
        $this->targetBag->setName($name);
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array &$array)
    {
        $this->targetBag->initialize($array);
    }

    /**
     * {@inheritdoc}
     */
    public function getStorageKey()
    {
        return $this->targetBag->getStorageKey();
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->targetBag->clear();
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($key)
    {
        return $this->targetBag->has($key);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($key)
    {
        return $this->targetBag->get($key);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($key, $value)
    {
        $this->targetBag->set($key, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($key)
    {
        $this->targetBag->remove($key);
    }

    /**
     * Adds an alias for $this->all().
     *
     * @deprecated Deprecated since Contao 4.0, to be removed in Contao 5.0.
     *             Use the all() method instead.
     */
    public function getData()
    {
        trigger_error('Using Session::getData() has been deprecated and will no longer work in Contao 5.0. Use the all() method instead.', E_USER_DEPRECATED);

        return $this->targetBag->all();
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
        trigger_error('Using Session::setData() has been deprecated and will no longer work in Contao 5.0. Use the replace() method instead.', E_USER_DEPRECATED);

        $this->targetBag->replace($attributes);
    }
}
