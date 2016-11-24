<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\ContaoManager\Bundle;

use Contao\ManagerBundle\ContaoManager\Dependency\UnresolvableDependenciesException;

/**
 * @author Andreas Schempp <https://github.com/aschempp>
 */
interface ConfigResolverInterface
{

    /**
     * Adds a configuration object.
     *
     * @param ConfigInterface $config
     *
     * @return $this
     */
    public function add(ConfigInterface $config);

    /**
     * Returns an array of bundle configs for development or production.
     *
     * @param bool $development
     *
     * @return array
     *
     * @throws UnresolvableDependenciesException
     */
    public function getBundleConfigs($development);
}
