<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Session;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Automatically starts the session if someone accesses $_SESSION.
 */
class LazySessionAccess implements \ArrayAccess, \Countable
{
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @param SessionInterface $session
     */
    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        $this->startSession();

        return $this->session->has($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        $this->startSession();

        return $this->session->get($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        $this->startSession();

        $this->session->set($offset, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        $this->startSession();

        $this->session->remove($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        $this->startSession();

        return \count($this->session->all());
    }

    /**
     * Starts the session.
     */
    private function startSession()
    {
        @trigger_error('Using $_SESSION has been deprecated and will no longer work in Contao 5.0. Use the Symfony session instead.', E_USER_DEPRECATED);

        $this->session->start();

        $_SESSION['BE_DATA'] = $this->session->getBag('contao_backend');
        $_SESSION['FE_DATA'] = $this->session->getBag('contao_frontend');
    }
}
