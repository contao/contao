<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\Api;

use Contao\ManagerBundle\Api\Command\GetConfigCommand;
use Contao\ManagerBundle\Api\Command\SetConfigCommand;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Console application for the Contao API.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class Application extends BaseApplication
{
    const VERSION = '1.0.0';

    /**
     * @var string
     */
    private $projectDir;

    /**
     * @var ManagerConfig
     */
    private $managerConfig;

    /**
     * Constructor.
     *
     * @param string $projectDir
     */
    public function __construct($projectDir)
    {
        $this->projectDir = realpath($projectDir) ?: $projectDir;;

        parent::__construct('contao-api', self::VERSION);
    }

    /**
     * Gets the manager config.
     *
     * @return ManagerConfig
     */
    public function getManagerConfig()
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
    public function setManagerConfig(ManagerConfig $managerConfig)
    {
        $this->managerConfig = $managerConfig;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureIO(InputInterface $input, OutputInterface $output)
    {
        $output->setDecorated(false);
        $input->setInteractive(false);
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultInputDefinition()
    {
        return new InputDefinition(
            [
                new InputArgument('command', InputArgument::REQUIRED, 'The command to execute'),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultCommands()
    {
        $commands = parent::getDefaultCommands();

        $commands[] = new GetConfigCommand();
        $commands[] = new SetConfigCommand();

        return $commands;
    }
}
