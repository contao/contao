<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\ContaoManager\Bundle;

/**
 * Configuration parser interface
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 * @author Andreas Schempp <https://github.com/aschempp>
 */
interface ParserInterface
{
    /**
     * Parses a configuration file.
     *
     * @param string      $resource
     * @param string|null $type
     *
     * @return ConfigInterface[]
     *
     * @throws \Exception
     */
    public function parse($resource, $type = null);

    /**
     * Returns whether this class supports the given config file.
     *
     * @param string      $resource
     * @param string|null $type
     */
    public function supports($resource, $type = null);
}
