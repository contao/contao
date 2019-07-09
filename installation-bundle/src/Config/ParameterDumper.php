<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\InstallationBundle\Config;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class ParameterDumper
{
    /**
     * @var string
     */
    private $configFile;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var array
     */
    private $parameters = ['parameters' => []];

    public function __construct(string $rootDir, Filesystem $filesystem = null)
    {
        $this->filesystem = $filesystem ?: new Filesystem();
        $parameters = [];

        if (file_exists($rootDir.'/config/parameters.yml') || !file_exists($rootDir.'/app/config/parameters.yml')) {
            $this->configFile = $rootDir.'/config/parameters.yml';
            $files = ['config/parameters.yml.dist', 'config/parameters.yml'];
        } else {
            $this->configFile = $rootDir.'/app/config/parameters.yml';
            $files = ['app/config/parameters.yml.dist', 'app/config/parameters.yml'];
        }

        foreach ($files as $file) {
            if (file_exists($rootDir.'/'.$file)) {
                $parameters[] = Yaml::parse(file_get_contents($rootDir.'/'.$file));
            }
        }

        if (0 !== count($parameters)) {
            $this->parameters = array_merge($this->parameters, ...$parameters);
        }
    }

    public function setParameter(string $name, $value): void
    {
        $this->parameters['parameters'][$name] = $value;
    }

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
            $this->configFile,
            "# This file has been auto-generated during installation\n".Yaml::dump($this->getEscapedValues())
        );
    }

    /**
     * Escapes % and @.
     *
     * @return array<string,string[]>
     *
     * @see http://symfony.com/doc/current/service_container/parameters.html#parameters-in-configuration-files
     */
    private function getEscapedValues(): array
    {
        $parameters = [];

        foreach ($this->parameters['parameters'] as $key => $value) {
            if (\is_string($value)) {
                if (0 === strncmp($value, '@', 1)) {
                    $value = '@'.$value;
                }

                if (false !== strpos($value, '%')) {
                    $value = str_replace('%', '%%', $value);
                }
            }

            $parameters[$key] = $value;
        }

        return ['parameters' => $parameters];
    }
}
