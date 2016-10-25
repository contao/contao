<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\ContaoManager\Dependency;

/**
 * @author Andreas Schempp <https://github.com/aschempp>
 */
interface DependentPluginInterface
{
    /**
     * Gets list of Composer packages names that must be loaded before this plugin.
     *
     * @return string[]
     */
    public function getPackageDependencies();
}
