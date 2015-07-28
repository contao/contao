<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
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
     * @param string $rootDir The root directory
     */
    public function __construct($rootDir)
    {
        $this->rootDir = $rootDir;
        $this->parameters = Yaml::parse(file_get_contents($rootDir . '/config/parameters.yml.dist'));
    }

    /**
     * Sets a parameter.
     *
     * @param string $name  The parameter name
     * @param mixed  $value The parameter value
     */
    public function setParameter($name, $value)
    {
        $this->parameters['parameters'][$name] = $value;
    }

    /**
     * Sets multiple parameters.
     *
     * @param array $params The parameters array
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
            $this->parameters['parameters']['secret'] = md5(uniqid(mt_rand(), true));
        }

        if ($this->parameters['parameters']['database_port']) {
            $this->parameters['parameters']['database_port'] = (int) $this->parameters['parameters']['database_port'];
        }

        file_put_contents($this->rootDir . '/config/parameters.yml', Yaml::dump($this->parameters));
    }
}
