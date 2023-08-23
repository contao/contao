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
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'version',
    description: 'Gets the Contao Manager API version and features.'
)]
class VersionCommand extends Command
{
    public function __construct(private readonly Application $application)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->write(json_encode(
            [
                'version' => Application::VERSION,
                'commands' => $this->getCommandNames(),
                'features' => $this->getFeatures(),
            ],
            JSON_THROW_ON_ERROR
        ));

        return 0;
    }

    /**
     * @return array<string>
     */
    private function getCommandNames(): array
    {
        return array_keys($this->application->all());
    }

    /**
     * @return array<string, array<string>>
     */
    private function getFeatures(): array
    {
        $plugins = $this->application->getPluginLoader()->getInstancesOf(ApiPluginInterface::class);
        $features = [];

        foreach ($plugins as $packageName => $plugin) {
            $features[$packageName] = $plugin->getApiFeatures();
        }

        return $features;
    }
}
