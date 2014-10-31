<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */

namespace Contao\CoreBundle\Autoload;

/**
 * Creates a configuration object
 *
 * @author Leo Feyer <https://contao.org>
 */
class ConfigFactory
{
    /**
     * Creates a configuration object and returns it
     *
     * @param array $config The configuration array
     *
     * @return Config The configuration object
     */
    public function create(array $config)
    {
        return Config::create()
            ->setName($config['name'])
            ->setClass($config['class'])
            ->setReplace($config['replace'])
            ->setEnvironments($config['environments'])
            ->setLoadAfter($config['load-after'])
        ;
    }
}
