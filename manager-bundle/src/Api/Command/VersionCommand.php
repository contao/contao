<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Api\Command;

use Contao\ManagerBundle\Api\Application;
use Contao\ManagerPlugin\Api\ApiPluginInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class VersionCommand extends Command
{
    /**
     * @var Application
     */
    private $application;

    public function __construct(Application $application)
    {
        parent::__construct();

        $this->application = $application;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('version')
            ->setDescription('Gets the Contao Manager API version and features.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $output->write(json_encode([
            'version' => Application::VERSION,
            'commands' => $this->getCommandNames(),
            'features' => $this->getFeatures(),
        ]));
    }

    /**
     * @return string[]
     */
    private function getCommandNames(): array
    {
        return array_keys($this->application->all());
    }

    /**
     * @return array<string,string[]>
     */
    private function getFeatures(): array
    {
        /** @var ApiPluginInterface[] $plugins */
        $plugins = $this->application->getPluginLoader()->getInstancesOf(ApiPluginInterface::class);

        $features = [];

        foreach ($plugins as $packageName => $plugin) {
            $features[$packageName] = $plugin->getApiFeatures();
        }

        return $features;
    }
}
