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
        $this->configFile = Path::join($projectDir, 'config/parameters.yml');
        $this->filesystem = $filesystem ?: new Filesystem();

        if (!$this->filesystem->exists($this->configFile)) {
            // Fallback to the legacy config file (see #566)
            $fallbackConfigFile = Path::join($projectDir, 'app/config/parameters.yml');

            if (!$this->filesystem->exists($fallbackConfigFile)) {
                return;
            }

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
     * Dumps the parameters into the parameters.yml file.
     */
    public function dump(): void
    {
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
            if (\is_string($value) && 0 === strncmp($value, '@', 1)) {
                $value = '@'.$value;
            }

            $parameters[$key] = $value;
        }

        return ['parameters' => $parameters];
    }
}
