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

use Contao\ManagerBundle\Api\Command\VersionCommand;
use Contao\ManagerPlugin\Api\ApiPluginInterface;
use Contao\ManagerPlugin\PluginLoader;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Application extends BaseApplication
{
    public const VERSION = '2';

    /**
     * @var string
     */
    private $projectDir;

    /**
     * @var PluginLoader
     */
    private $pluginLoader;

    /**
     * @var ManagerConfig
     */
    private $managerConfig;

    /**
     * @param string $projectDir
     */
    public function __construct(string $projectDir)
    {
        $this->projectDir = realpath($projectDir) ?: $projectDir;

        parent::__construct('contao-api', self::VERSION);
    }

    /**
     * Gets the project directory (Contao root).
     *
     * @return string
     */
    public function getProjectDir(): string
    {
        return $this->projectDir;
    }

    /**
     * Gets the plugin loader.
     *
     * @return PluginLoader
     */
    public function getPluginLoader(): PluginLoader
    {
        if (null === $this->pluginLoader) {
            $this->pluginLoader = new PluginLoader();

            $config = $this->getManagerConfig()->all();

            if (isset($config['contao_manager']['disabled_packages'])
                && \is_array($config['contao_manager']['disabled_packages'])
            ) {
                $this->pluginLoader->setDisabledPackages($config['contao_manager']['disabled_packages']);
            }
        }

        return $this->pluginLoader;
    }

    /**
     * Sets the plugin loader.
     *
     * @param PluginLoader $pluginLoader
     */
    public function setPluginLoader(PluginLoader $pluginLoader): void
    {
        $this->pluginLoader = $pluginLoader;
    }

    /**
     * Gets the manager config.
     *
     * @return ManagerConfig
     */
    public function getManagerConfig(): ManagerConfig
    {
        if (null === $this->managerConfig) {
            $this->managerConfig = new ManagerConfig($this->projectDir);
        }

        return $this->managerConfig;
    }

    /**
     * Sets the manager config.
     *
     * @param ManagerConfig $managerConfig
     */
    public function setManagerConfig(ManagerConfig $managerConfig): void
    {
        $this->managerConfig = $managerConfig;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureIO(InputInterface $input, OutputInterface $output): void
    {
        $output->setDecorated(false);
        $input->setInteractive(false);
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultInputDefinition(): InputDefinition
    {
        return new InputDefinition([
            new InputArgument('command', InputArgument::REQUIRED, 'The command to execute'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultCommands(): array
    {
        $commands = parent::getDefaultCommands();
        $commands[] = new VersionCommand($this);

        /** @var ApiPluginInterface $plugin */
        foreach ($this->getPluginLoader()->getInstancesOf(ApiPluginInterface::class) as $plugin) {
            foreach ($plugin->getApiCommands() as $class) {
                if (!is_a($class, Command::class, true)) {
                    throw new \RuntimeException(sprintf('"%s" is not a console command.', $class));
                }

                $commands[] = new $class($this);
            }
        }

        return $commands;
    }
}
