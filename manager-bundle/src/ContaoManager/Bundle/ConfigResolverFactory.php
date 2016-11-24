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
 * Factory for ConfigResolverInterface
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class ConfigResolverFactory
{
    /**
     * Creates an instance of ConfigResolverInterface.
     *
     * @return ConfigResolverInterface
     */
    public function create()
    {
        return new ConfigResolver();
    }
}
