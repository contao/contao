<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Command;

use Contao\CoreBundle\HttpKernel\Bundle\ContaoModuleBundle;
use Contao\ManagerBundle\HttpKernel\ContaoKernel;
use Contao\ManagerPlugin\Api\ApiPluginInterface;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Parser\DelegatingParser;
use Contao\ManagerPlugin\Bundle\Parser\IniParser;
use Contao\ManagerPlugin\Bundle\Parser\JsonParser;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Config\ConfigPluginInterface;
use Contao\ManagerPlugin\Config\ExtensionPluginInterface;
use Contao\ManagerPlugin\Dependency\DependentPluginInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Path;

#[AsCommand(
    name: 'debug:plugins',
    description: 'Displays the Contao Manager plugin configurations.',
)]
class DebugPluginsCommand extends Command
{
    private SymfonyStyle|null $io = null;

    public function __construct(private readonly ContaoKernel $kernel)
    {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::OPTIONAL, 'The plugin class or package name')
            ->addOption('bundles', null, InputOption::VALUE_NONE, 'List all bundles or the bundle configuration of the given plugin')
        ;
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $name = $input->getArgument('name');

        if ($name) {
            return $this->showPlugin($name, $input);
        }

        if ($input->getOption('bundles')) {
            return $this->listBundles();
        }

        return $this->listPlugins();
    }

    private function listPlugins(): int
    {
        $title = 'Contao Manager Plugins';

        $headers = [
            [
                new TableCell('Plugin Class', ['rowspan' => 2]),
                new TableCell('Composer Package', ['rowspan' => 2]),
                new TableCell('Features / Plugin Interfaces', ['colspan' => 6]),
            ],
            ['Bundle', 'Routing', 'Config', 'Extension', 'Dependent', 'API'],
        ];

        $rows = [];
        $plugins = $this->kernel->getPluginLoader()->getInstances();
        $check = '\\' === \DIRECTORY_SEPARATOR ? '1' : "\xE2\x9C\x94"; // HEAVY CHECK MARK (U+2714)

        foreach ($plugins as $packageName => $plugin) {
            $rows[] = [
                $plugin::class,
                $packageName,
                $plugin instanceof BundlePluginInterface ? $check : '',
                $plugin instanceof RoutingPluginInterface ? $check : '',
                $plugin instanceof ConfigPluginInterface ? $check : '',
                $plugin instanceof ExtensionPluginInterface ? $check : '',
                $plugin instanceof DependentPluginInterface ? $check : '',
                $plugin instanceof ApiPluginInterface ? $check : '',
            ];
        }

        $this->io->title($title);
        $this->io->table($headers, $rows);

        return Command::SUCCESS;
    }

    private function listBundles(): int
    {
        $title = 'Registered Bundles in Loading Order';
        $headers = ['Bundle Name', 'Contao Resources Path'];
        $rows = [];
        $bundles = $this->kernel->getBundles();

        foreach ($bundles as $name => $bundle) {
            $path = '';
            $class = $bundle::class;

            if (ContaoModuleBundle::class === $class) {
                $path = Path::join('system/modules', $name);
            } else {
                $reflection = new \ReflectionClass($class);

                if (is_dir($dir = Path::join($reflection->getFileName(), '../Resources/contao'))) {
                    $path = Path::makeRelative($dir, $this->kernel->getProjectDir());
                }
            }

            $rows[] = [$bundle->getName(), $path];
        }

        $this->io->title($title);
        $this->io->table($headers, $rows);

        return Command::SUCCESS;
    }

    private function showPlugin(string $name, InputInterface $input): int
    {
        if ($input->getOption('bundles')) {
            return $this->showPluginBundles($name);
        }

        $choices = [];
        [, $plugin] = $this->findPlugin($name);

        if ($plugin instanceof BundlePluginInterface) {
            $choices[] = 'Bundle';
        }

        $result = $this->io->choice('Which feature do you want to debug?', $choices);

        if ('Bundle' === $result) {
            return $this->showPluginBundles($name);
        }

        return -1;
    }

    private function showPluginBundles(string $name): int
    {
        [, $plugin] = $this->findPlugin($name);

        if (null === $plugin) {
            return -1;
        }

        if (!$plugin instanceof BundlePluginInterface) {
            $this->io->error(
                sprintf(
                    'The "%s" plugin does not implement the "%s" interface.',
                    $plugin::class,
                    BundlePluginInterface::class,
                ),
            );

            return -1;
        }

        $title = sprintf('Bundles Registered by Plugin "%s"', $plugin::class);
        $headers = ['Bundle', 'Replaces', 'Load After', 'Environment'];
        $rows = [];
        $configs = $plugin->getBundles($this->getBundleParser());

        foreach ($configs as $config) {
            $rows[] = [
                $config->getName(),
                implode("\n", $config->getReplace()),
                implode("\n", $config->getLoadAfter()),
                match (true) {
                    $config->loadInProduction() && $config->loadInDevelopment() => 'All',
                    $config->loadInProduction() => 'Production',
                    $config->loadInDevelopment() => 'Development',
                },
            ];

            $rows[] = new TableSeparator();
        }

        // Remove the last separator
        array_pop($rows);

        $this->io->title($title);
        $this->io->table($headers, $rows);

        return Command::SUCCESS;
    }

    private function findPlugin(string $name): array|null
    {
        $plugins = $this->kernel->getPluginLoader()->getInstances();

        if (isset($plugins[$name])) {
            return [$name, $plugins[$name]];
        }

        foreach ($plugins as $packageName => $plugin) {
            if ($plugin::class === $name) {
                return [$packageName, $plugin];
            }
        }

        $this->io->error(sprintf('No plugin with the class or package name "%s" found.', $name));

        return null;
    }

    private function getBundleParser(): ParserInterface
    {
        $parser = new DelegatingParser();
        $parser->addParser(new JsonParser());
        $parser->addParser(new IniParser(Path::join($this->kernel->getProjectDir(), 'system/modules')));

        return $parser;
    }
}
