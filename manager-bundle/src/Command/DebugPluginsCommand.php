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
use Contao\ManagerPlugin\Api\ApiPluginInterface;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Parser\DelegatingParser;
use Contao\ManagerPlugin\Bundle\Parser\IniParser;
use Contao\ManagerPlugin\Bundle\Parser\JsonParser;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Config\ConfigPluginInterface;
use Contao\ManagerPlugin\Config\ExtensionPluginInterface;
use Contao\ManagerPlugin\Dependency\DependentPluginInterface;
use Contao\ManagerPlugin\PluginLoader;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

class DebugPluginsCommand extends Command
{
    protected static $defaultName = 'debug:plugins';

    /**
     * @var PluginLoader
     */
    private $pluginLoader;

    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var string
     */
    private $projectDir;

    /**
     * @var SymfonyStyle
     */
    private $io;

    public function __construct(PluginLoader $pluginLoader, KernelInterface $kernel, string $projectDir)
    {
        parent::__construct();

        $this->pluginLoader = $pluginLoader;
        $this->kernel = $kernel;
        $this->projectDir = $projectDir;
    }


    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setDefinition([
                new InputArgument('name', InputArgument::OPTIONAL, 'The plugin class or package name'),
            ])
            ->addOption('bundles', null, InputOption::VALUE_NONE, 'List all bundles or bundles config of the specified plugin')
            ->setDescription('Displays the configuration of Contao Manager plugins.')
        ;
    }

    /**
     * {@inheritdoc}
     */
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
        $title = 'Contao Manager plugins with their package name';
        $headers = [
            [new TableCell('Plugin class', ['rowspan' => 2]), new TableCell('Composer package', ['rowspan' => 2]), new TableCell('Features / Plugin Interfaces', ['colspan' => 6])],
            ['Bundle', 'Routing', 'Config', 'Extension', 'Dependent', 'API'],
        ];
        $rows = [];

        $plugins = $this->pluginLoader->getInstances();
        $check = '\\' === \DIRECTORY_SEPARATOR ? '1' : "\xE2\x9C\x94"; // HEAVY CHECK MARK (U+2714)

        foreach ($plugins as $packageName => $plugin) {
            $rows[] = [
                get_class($plugin),
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

        return 0;
    }

    private function listBundles(): int
    {
        $title = 'Available registered bundles in loading order';
        $headers = ['Bundle name', 'Contao Resources path'];
        $rows = [];

        $bundles = $this->kernel->getBundles();

        foreach ($bundles as $name => $bundle) {
            $path = '';
            $class = get_class($bundle);

            if (ContaoModuleBundle::class === $class) {
                $path = sprintf('system/modules/%s', $name);
            } else {
                $reflection = new \ReflectionClass($class);

                if (is_dir($dir = \dirname($reflection->getFileName()).'/Resources/contao')) {
                    $path = (new Filesystem())->makePathRelative($dir, $this->projectDir);
                }
            }

            $rows[] = [$bundle->getName(), $path];
        }

        $this->io->title($title);
        $this->io->table($headers, $rows);

        return 0;
    }

    private function showPlugin(string $name, InputInterface $input): int
    {
        if ($input->getOption('bundles')) {
            return $this->showPluginBundles($name);
        }

        $choices = [];
        [, $plugin] = $this->findPlugin($name);

        if ($plugin instanceof BundlePluginInterface) {
            $choices['BundlePluginInterface'] = 'Symfony bundles loaded by this plugin.';
        }

        $result = $this->io->choice(
            sprintf('Which features of the "%s" plugin do you want to debug?', $name),
            $choices
        );

        switch ($result) {
            case 'BundlePluginInterface':
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
                    "The plugin \"%s\" does not register bundles.\n(It does not implement the \"%s\" interface.)",
                    get_class($plugin),
                    BundlePluginInterface::class
                )
            );

            return -1;
        }

        $title = sprintf('Bundles registered by plugin "%s"', get_class($plugin));
        $headers = ['Bundle', 'Replaces', 'Load after', 'Environment'];
        $rows = [];

        $configs = $plugin->getBundles($this->getBundleParser());

        foreach ($configs as $config) {
            $rows[] = [
                $config->getName(),
                implode("\n", $config->getReplace()),
                implode("\n", $config->getLoadAfter()),
                $config->loadInProduction() && $config->loadInDevelopment() ? 'All' : ($config->loadInProduction() ? 'Production' : 'Development'),
            ];
            $rows[] = new TableSeparator();
        }

        // Remove last separator
        array_pop($rows);

        $this->io->title($title);
        $this->io->table($headers, $rows);

        return 0;
    }

    private function findPlugin(string $name): ?array
    {
        $plugins = $this->pluginLoader->getInstances();

        if (isset($plugins[$name])) {
            return [$name, $plugins[$name]];
        }

        foreach ($plugins as $packageName => $plugin) {
            if (get_class($plugin) === $name) {
                return [$packageName, $plugin];
            }
        }

        $this->io->error(sprintf('No plugin with class or package name "%s" was found', $name));

        return null;
    }

    private function getBundleParser(): ParserInterface
    {
        $parser = new DelegatingParser();
        $parser->addParser(new JsonParser());
        $parser->addParser(new IniParser($this->kernel->getProjectDir().'/system/modules'));

        return $parser;
    }
}
