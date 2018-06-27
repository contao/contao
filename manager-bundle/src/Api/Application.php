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

use Contao\ManagerBundle\Api\Command\GetConfigCommand;
use Contao\ManagerBundle\Api\Command\GetDotEnvCommand;
use Contao\ManagerBundle\Api\Command\RemoveDotEnvCommand;
use Contao\ManagerBundle\Api\Command\SetConfigCommand;
use Contao\ManagerBundle\Api\Command\SetDotEnvCommand;
use Contao\ManagerBundle\Api\Command\VersionCommand;
use Symfony\Component\Console\Application as BaseApplication;
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

        $commands[] = new VersionCommand();
        $commands[] = new GetConfigCommand($this->getManagerConfig());
        $commands[] = new SetConfigCommand($this->getManagerConfig());
        $commands[] = new GetDotEnvCommand($this->projectDir);
        $commands[] = new SetDotEnvCommand($this->projectDir);
        $commands[] = new RemoveDotEnvCommand($this->projectDir);

        return $commands;
    }
}
