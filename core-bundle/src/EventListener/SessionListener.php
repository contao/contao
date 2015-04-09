<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Registers the Contao front end and back end session bags.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 */
class SessionListener
{
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * Constructor.
     *
     * @param SessionInterface $session
     */
    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * Registers the Contao front end and back end session bags.
     */
    public function registerContaoAttributeBags()
    {
        $beBag = new AttributeBag('_contao_be_attributes');
        $beBag->setName('contao_backend');

        $this->session->registerBag($beBag);

        $feBag = new AttributeBag('_contao_fe_attributes');
        $feBag->setName('contao_frontend');

        $this->session->registerBag($feBag);
    }
}
