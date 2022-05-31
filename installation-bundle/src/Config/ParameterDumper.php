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
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Yaml\Yaml;

class ParameterDumper
{
    private string $configFile;
    private Filesystem $filesystem;
    private array $parameters = ['parameters' => []];

    public function __construct(string $projectDir, Filesystem $filesystem = null)
    {
        $this->configFile = Path::join($projectDir, 'config/parameters.yaml');
        $this->filesystem = $filesystem ?: new Filesystem();

        if (!$this->filesystem->exists($this->configFile)) {
            $fallbackConfigFile = Path::join($projectDir, 'config/parameters.yml');

            if (!$this->filesystem->exists($fallbackConfigFile)) {
                return;
            }

            trigger_deprecation('contao/installation-bundle', '5.0', 'Using a parameters.yml file has been deprecated and will no longer work in Contao 6.0. Use a parameters.yaml file instead.');

            $this->configFile = $fallbackConfigFile;
        }

        $parameters = Yaml::parse(file_get_contents($this->configFile));

        if (0 !== \count($parameters)) {
            $this->parameters = array_merge($this->parameters, $parameters);
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
     * Dumps the parameters into the parameters.yaml file.
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
     * @return array<string, array<string>>
     *
     * @see https://symfony.com/doc/current/service_container.html#service-parameters
     */
    private function getEscapedValues(): array
    {
        $parameters = [];

        foreach ($this->parameters['parameters'] as $key => $value) {
            if (\is_string($value) && str_starts_with($value, '@')) {
                $value = '@'.$value;
            }

            $parameters[$key] = $value;
        }

        return ['parameters' => $parameters];
    }
}
