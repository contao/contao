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

/**
 * @internal
 */
class Application extends BaseApplication
{
    final public const VERSION = '2';

    private readonly string $projectDir;

    private PluginLoader|null $pluginLoader = null;

    private ManagerConfig|null $managerConfig = null;

    public function __construct(string $projectDir)
    {
        $this->projectDir = realpath($projectDir) ?: $projectDir;

        parent::__construct('contao-api', self::VERSION);
    }

    public function getProjectDir(): string
    {
        return $this->projectDir;
    }

    public function getPluginLoader(): PluginLoader
    {
        if (!$this->pluginLoader) {
            $this->pluginLoader = new PluginLoader();

            $config = $this->getManagerConfig()->all();

            if (
                isset($config['contao_manager']['disabled_packages'])
                && \is_array($config['contao_manager']['disabled_packages'])
            ) {
                $this->pluginLoader->setDisabledPackages($config['contao_manager']['disabled_packages']);
            }
        }

        return $this->pluginLoader;
    }

    public function setPluginLoader(PluginLoader $pluginLoader): void
    {
        $this->pluginLoader = $pluginLoader;
    }

    public function getManagerConfig(): ManagerConfig
    {
        return $this->managerConfig ??= new ManagerConfig($this->projectDir);
    }

    public function setManagerConfig(ManagerConfig $managerConfig): void
    {
        $this->managerConfig = $managerConfig;
    }

    protected function configureIO(InputInterface $input, OutputInterface $output): void
    {
        $output->setDecorated(false);
        $input->setInteractive(false);
    }

    protected function getDefaultInputDefinition(): InputDefinition
    {
        return new InputDefinition([
            new InputArgument('command', InputArgument::REQUIRED, 'The command to execute'),
        ]);
    }

    protected function getDefaultCommands(): array
    {
        $commands = parent::getDefaultCommands();
        $commands[] = new VersionCommand($this);

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
