<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\InstallationBundle\Config;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

/**
 * Dumps the parameters into the parameters.yml file.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class ParameterDumper
{
    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var array
     */
    private $parameters = [
        'parameters' => [
            'database_host' => 'localhost',
            'database_port' => 3306,
            'database_user' => null,
            'database_password' => null,
            'database_name' => null,
            'mailer_transport' => 'mail',
            'mailer_host' => '127.0.0.1',
            'mailer_user' => null,
            'mailer_password' => null,
            'mailer_port' => 25,
            'mailer_encryption' => null,
            'prepend_locale' => false,
            'secret' => 'ThisTokenIsNotSoSecretChangeIt',
        ],
    ];

    /**
     * Constructor.
     *
     * @param string     $rootDir
     * @param Filesystem $filesystem
     */
    public function __construct($rootDir, Filesystem $filesystem = null)
    {
        $this->rootDir = $rootDir;
        $this->filesystem = $filesystem ?: new Filesystem();

        foreach (['config/parameters.yml.dist', 'config/parameters.yml'] as $file) {
            if (file_exists($rootDir.'/'.$file)) {
                $this->parameters = array_merge(
                    $this->parameters,
                    Yaml::parse(file_get_contents($rootDir.'/'.$file))
                );
            }
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

        $this->filesystem->dumpFile(
            $this->rootDir.'/config/parameters.yml',
            "# This file has been auto-generated during installation\n".Yaml::dump($this->parameters)
        );
    }
}
