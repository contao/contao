<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\InstallationBundle\Config;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

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
    private $parameters = ['parameters' => []];

    /**
     * @param string          $rootDir
     * @param Filesystem|null $filesystem
     */
    public function __construct(string $rootDir, Filesystem $filesystem = null)
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
    public function setParameter(string $name, $value): void
    {
        $this->parameters['parameters'][$name] = $value;
    }

    /**
     * Sets multiple parameters.
     *
     * @param array $params
     */
    public function setParameters(array $params): void
    {
        foreach ($params['parameters'] as $name => $value) {
            $this->setParameter($name, $value);
        }
    }

    /**
     * Dumps the parameters into the parameters.yml file.
     */
    public function dump(): void
    {
        if (
            empty($this->parameters['parameters']['secret']) ||
            'ThisTokenIsNotSoSecretChangeIt' === $this->parameters['parameters']['secret']
        ) {
            $this->parameters['parameters']['secret'] = bin2hex(random_bytes(32));
        }

        if (isset($this->parameters['parameters']['database_port'])) {
            $this->parameters['parameters']['database_port'] = (int) $this->parameters['parameters']['database_port'];
        }

        $this->filesystem->dumpFile(
            $this->rootDir.'/config/parameters.yml',
            "# This file has been auto-generated during installation\n".Yaml::dump($this->getEscapedValues())
        );
    }

    /**
     * Escapes % and @.
     *
     * @return array<string,array>
     */
    private function getEscapedValues(): array
    {
        $parameters = [];

        foreach ($this->parameters['parameters'] as $key => $value) {
            if (false !== strpos($value, '%')) {
                $parameters[$key] = str_replace('%', '%%', $value);
            } else {
                $parameters[$key] = $value;
            }
        }

        return ['parameters' => $parameters];
    }
}
