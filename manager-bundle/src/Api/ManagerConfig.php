<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Api;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Yaml\Yaml;

/**
 * @internal
 */
class ManagerConfig
{
    private string $configFile;
    private readonly Filesystem $filesystem;
    private array|null $config = null;

    public function __construct(string $projectDir, Filesystem|null $filesystem = null)
    {
        if (false !== ($realpath = realpath($projectDir))) {
            $projectDir = (string) $realpath;
        }

        $this->filesystem = $filesystem ?: new Filesystem();
        $this->configFile = Path::join($projectDir, 'config/contao-manager.yaml');

        if ($this->filesystem->exists($this->configFile)) {
            return;
        }

        if ($this->filesystem->exists($path = Path::join($projectDir, 'config/contao-manager.yml'))) {
            trigger_deprecation('contao/manager-bundle', '5.0', 'Using a contao-manager.yml file has been deprecated and will no longer work in Contao 6.0. Use a contao-manager.yaml file instead.');

            $this->configFile = $path;
        }
    }

    public function all(): array
    {
        if (null === $this->config) {
            $this->read();
        }

        return $this->config;
    }

    public function read(): array
    {
        $this->config = [];

        if (is_file($this->configFile)) {
            $config = Yaml::parse(file_get_contents($this->configFile));

            if (\is_array($config)) {
                $this->config = $config;
            }
        }

        return $this->config;
    }

    public function write(array $config): void
    {
        $this->config = $config;

        $this->filesystem->dumpFile($this->configFile, Yaml::dump($config));
    }
}
