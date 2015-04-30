<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Session\Attribute;

use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

/**
 * Provides an array access adapter for a session attribute bag.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 */
class AttributeBagAdapter implements \ArrayAccess
{
    /**
     * @var AttributeBagInterface
     */
    private $targetBag;

    /**
     * Constructor.
     *
     * @param AttributeBagInterface $targetBag The target bag
     */
    public function __construct(AttributeBagInterface $targetBag)
    {
        $this->targetBag = $targetBag;
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
}
