<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Adapter;

use Contao\Config;
use Contao\CoreBundle\Framework\Adapter\GeneralAdapter;

/**
 * Provides an adapter for the Contao Config class.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 *
 * @internal
 * @deprecated Deprecated since Contao 4.1, to be removed in Contao 5.
 *             Use the framework adapters instead.
 */
class ConfigAdapter extends GeneralAdapter
{
    /**
     * Constructor.
     *
     * @param string $class
     */
    public function __construct($class = null)
    {
        parent::__construct('Config');
    }

    /**
     * Used for Backwards Compatibility between 4.0 and 4.1.
     */
    public function initialize()
    {
        $this->__call('getInstance');
    }
}
