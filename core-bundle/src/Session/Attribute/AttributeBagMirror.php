<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Session\Attribute;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;

/**
 * Provides an ArrayAccess mirror for a session AttributeBag.
 * Used for BC for $_SESSION['FE_DATA'] and $_SESSION['BE_DATA'].
 *
 * @author Yanick Witschi <https://github.com/toflar>
 */
class AttributeBagMirror implements \ArrayAccess
{
    /**
     * @var AttributeBag
     */
    private $targetBag;


    /**
     * Creates an AttributeBagMirror with a target bag
     *
     * @param AttributeBag $targetBag
     */
    public function __construct(AttributeBag $targetBag)
    {
        $this->targetBag = $targetBag;
    }

    /**
    * ArrayAccess has argument.
    *
    * @param string $key Array key.
    *
    * @return bool
    */
    public function offsetExists($key)
    {
        return $this->targetBag->has($key);
    }

    /**
     * ArrayAccess for argument getter.
     *
     * @param string $key Array key.
     *
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->targetBag->get($key);
    }

    /**
     * ArrayAccess for argument setter.
     *
     * @param string $key   Array key to set.
     * @param mixed  $value Value.
     */
    public function offsetSet($key, $value)
    {
        $this->targetBag->set($key, $value);
    }

    /**
     * ArrayAccess for unset argument.
     *
     * @param string $key Array key.
     */
    public function offsetUnset($key)
    {
        $this->targetBag->remove($key);
    }
}