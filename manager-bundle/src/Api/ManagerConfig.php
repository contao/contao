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
    private Filesystem $filesystem;
    private ?array $config = null;

    public function __construct(string $projectDir, Filesystem $filesystem = null)
    {
        if (false !== ($realpath = realpath($projectDir))) {
            $projectDir = (string) $realpath;
        }

        $this->configFile = Path::join($projectDir, 'config/contao-manager.yml');
        $this->filesystem = $filesystem ?: new Filesystem();
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
