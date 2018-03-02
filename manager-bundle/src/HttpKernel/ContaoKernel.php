<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\HttpKernel;

use Contao\ManagerBundle\Api\ManagerConfig;
use Contao\ManagerPlugin\Bundle\BundleLoader;
use Contao\ManagerPlugin\Bundle\Config\ConfigResolverFactory;
use Contao\ManagerPlugin\Bundle\Parser\DelegatingParser;
use Contao\ManagerPlugin\Bundle\Parser\IniParser;
use Contao\ManagerPlugin\Bundle\Parser\JsonParser;
use Contao\ManagerPlugin\Config\ConfigPluginInterface;
use Contao\ManagerPlugin\Config\ContainerBuilder as PluginContainerBuilder;
use Contao\ManagerPlugin\PluginLoader;
use ProxyManager\Configuration;
use Symfony\Bridge\ProxyManager\LazyProxy\Instantiator\RuntimeInstantiator;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

class ContaoKernel extends Kernel
{
    /**
     * @var string
     */
    protected static $projectDir;

    /**
     * @var PluginLoader
     */
    private $pluginLoader;

    /**
     * @var BundleLoader
     */
    private $bundleLoader;

    /**
     * @var ManagerConfig
     */
    private $managerConfig;

    /**
     * {@inheritdoc}
     */
    public function registerBundles(): array
    {
        $bundles = [];

        $this->addBundlesFromPlugins($bundles);

        return $bundles;
    }

    /**
     * {@inheritdoc}
     */
    public function getProjectDir(): string
    {
        if (null === self::$projectDir) {
            throw new \LogicException('ContaoKernel::setProjectDir() must be called to initialize the Contao kernel');
        }

        return self::$projectDir;
    }

    /**
     * {@inheritdoc}
     */
    public function getRootDir(): string
    {
        if (null === $this->rootDir) {
            $this->rootDir = $this->getProjectDir().'/app';
        }

        return $this->rootDir;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheDir(): string
    {
        return $this->getProjectDir().'/var/cache/'.$this->getEnvironment();
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDir(): string
    {
        return $this->getProjectDir().'/var/logs';
    }

    /**
     * Gets the plugin loader.
     *
     * @return PluginLoader
     */
    public function getPluginLoader(): PluginLoader
    {
        if (null === $this->pluginLoader) {
            $this->pluginLoader = new PluginLoader($this->getProjectDir().'/vendor/composer/installed.json');
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
     * Gets the bundle loader.
     *
     * @return BundleLoader
     */
    public function getBundleLoader(): BundleLoader
    {
        if (null === $this->bundleLoader) {
            $parser = new DelegatingParser();
            $parser->addParser(new JsonParser());
            $parser->addParser(new IniParser($this->getProjectDir().'/system/modules'));

            $this->bundleLoader = new BundleLoader($this->getPluginLoader(), new ConfigResolverFactory(), $parser);
        }

        return $this->bundleLoader;
    }

    /**
     * Sets the bundle loader.
     *
     * @param BundleLoader $bundleLoader
     */
    public function setBundleLoader(BundleLoader $bundleLoader): void
    {
        $this->bundleLoader = $bundleLoader;
    }

    /**
     * Gets the manager config.
     *
     * @return ManagerConfig
     */
    public function getManagerConfig(): ManagerConfig
    {
        if (null === $this->managerConfig) {
            $this->managerConfig = new ManagerConfig($this->getProjectDir());
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
    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $rootDir = $this->getRootDir();

        if (file_exists($rootDir.'/config/parameters.yml')) {
            $loader->load($rootDir.'/config/parameters.yml');
        }

        $config = $this->getManagerConfig()->all();
        $plugins = $this->getPluginLoader()->getInstancesOf(PluginLoader::CONFIG_PLUGINS);

        /** @var ConfigPluginInterface[] $plugins */
        foreach ($plugins as $plugin) {
            $plugin->registerContainerConfiguration($loader, $config);
        }

        // Reload the parameters.yml file
        if (file_exists($rootDir.'/config/parameters.yml')) {
            $loader->load($rootDir.'/config/parameters.yml');
        }

        if (file_exists($rootDir.'/config/config_'.$this->getEnvironment().'.yml')) {
            $loader->load($rootDir.'/config/config_'.$this->getEnvironment().'.yml');
        } elseif (file_exists($rootDir.'/config/config.yml')) {
            $loader->load($rootDir.'/config/config.yml');
        }

        // The "mail" transport has been removed in SwiftMailer 6, so use "sendmail" instead
        $loader->load(
            function (ContainerBuilder $container): void {
                if ('mail' === $container->getParameter('mailer_transport')) {
                    $container->setParameter('mailer_transport', 'sendmail');
                }
            }
        );
    }

    /**
     * Sets the project directory (the Contao kernel does not know its location).
     *
     * @param string $projectDir
     */
    public static function setProjectDir(string $projectDir): void
    {
        self::$projectDir = realpath($projectDir) ?: $projectDir;
    }

    /**
     * {@inheritdoc}
     */
    protected function getContainerBuilder(): PluginContainerBuilder
    {
        $container = new PluginContainerBuilder($this->getPluginLoader(), []);
        $container->getParameterBag()->add($this->getKernelParameters());

        if (class_exists(Configuration::class) && class_exists(RuntimeInstantiator::class)) {
            $container->setProxyInstantiator(new RuntimeInstantiator());
        }

        return $container;
    }

    /**
     * {@inheritdoc}
     */
    protected function initializeContainer(): void
    {
        parent::initializeContainer();

        // Set the plugin loader again so it is available at runtime (synthetic service)
        $this->getContainer()->set('contao_manager.plugin_loader', $this->getPluginLoader());
    }

    /**
     * Adds bundles from plugins to the given array.
     *
     * @param array $bundles
     */
    private function addBundlesFromPlugins(array &$bundles): void
    {
        $configs = $this->getBundleLoader()->getBundleConfigs(
            'dev' === $this->getEnvironment(),
            $this->debug ? null : $this->getCacheDir().'/bundles.map'
        );

        foreach ($configs as $config) {
            $bundles[$config->getName()] = $config->getBundleInstance($this);
        }

        // Autoload AppBundle for convenience
        $appBundle = 'AppBundle\AppBundle';

        if (!isset($bundles[$appBundle]) && class_exists($appBundle)) {
            $bundles[$appBundle] = new $appBundle();
        }
    }
}
