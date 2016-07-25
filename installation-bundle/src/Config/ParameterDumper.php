<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\InstallationBundle\Config;

use Symfony\Component\Yaml\Yaml;

/**
 * Dumps the parameters into the paramters.yml file.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ParameterDumper
{
    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var array
     */
    private $parameters;

    /**
     * Constructor.
     *
     * @param string $rootDir
     */
    public function __construct($rootDir)
    {
        $this->rootDir = $rootDir;
        $this->parameters = Yaml::parse(file_get_contents($rootDir.'/config/parameters.yml.dist'));

        if (file_exists($rootDir.'/config/parameters.yml')) {
            $this->parameters = array_merge(
                $this->parameters,
                Yaml::parse(file_get_contents($rootDir.'/config/parameters.yml'))
            );
        }
    }

    /**
     * Sets a parameter.
     *
     * @param string $name
     * @param mixed  $value
     */
    public function setParameter($name, $value)
    {
        $this->parameters['parameters'][$name] = $value;
    }

    /**
     * Sets multiple parameters.
     *
     * @param array $params
     */
    public function setParameters(array $params)
    {
        foreach ($params['parameters'] as $name => $value) {
            $this->setParameter($name, $value);
        }
    }

    /**
     * Dumps the parameters into the parameters.yml file.
     */
    public function dump()
    {
        if ('ThisTokenIsNotSoSecretChangeIt' === $this->parameters['parameters']['secret']) {
            $this->parameters['parameters']['secret'] = bin2hex(random_bytes(32));
        }

        if (isset($this->parameters['parameters']['database_port'])) {
            $this->parameters['parameters']['database_port'] = (int) $this->parameters['parameters']['database_port'];
        }

        file_put_contents(
            $this->rootDir.'/config/parameters.yml',
            "# This file has been auto-generated during installation\n".Yaml::dump($this->parameters)
        );
    }
}
