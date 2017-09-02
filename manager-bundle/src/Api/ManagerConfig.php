<?php

namespace Contao\ManagerBundle\Api;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class ManagerConfig
{
    /**
     * @var string
     */
    private $configFile;

    /**
     * @var null|Filesystem
     */
    private $filesystem;

    /**
     * @var array
     */
    private $config;

    /**
     * Constructor.
     *
     * @param string          $projectDir
     * @param Filesystem|null $filesystem
     */
    public function __construct($projectDir, Filesystem $filesystem = null)
    {
        $projectDir = realpath($projectDir) ?: $projectDir;

        $this->configFile = $projectDir.'/app/config/contao-manager.yml';
        $this->filesystem = $filesystem ?: new Filesystem();
    }

    public function all()
    {
        if (null === $this->config) {
            $this->read();
        }

        return $this->config;
    }

    /**
     * @return array
     */
    public function read()
    {
        if (!is_file($this->configFile)) {
            $this->config = [];
        } else {
            $this->config = Yaml::parse(file_get_contents($this->configFile));
        }

        return $this->config;
    }

    /**
     * @param array $config
     */
    public function write(array $config)
    {
        $this->config = $config;

        $this->filesystem->dumpFile(
            $this->configFile,
            Yaml::dump($config)
        );
    }
}
