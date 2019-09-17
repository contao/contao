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

use AppBundle\AppBundle;
use Contao\ManagerBundle\Api\ManagerConfig;
use Contao\ManagerBundle\ContaoManager\Plugin;
use Contao\ManagerPlugin\Bundle\BundleLoader;
use Contao\ManagerPlugin\Bundle\Config\ConfigResolverFactory;
use Contao\ManagerPlugin\Bundle\Parser\DelegatingParser;
use Contao\ManagerPlugin\Bundle\Parser\IniParser;
use Contao\ManagerPlugin\Bundle\Parser\JsonParser;
use Contao\ManagerPlugin\Config\ConfigPluginInterface;
use Contao\ManagerPlugin\Config\ContainerBuilder as PluginContainerBuilder;
use Contao\ManagerPlugin\PluginLoader;
use FOS\HttpCache\SymfonyCache\HttpCacheProvider;
use ProxyManager\Configuration;
use Symfony\Bridge\ProxyManager\LazyProxy\Instantiator\RuntimeInstantiator;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Debug\Debug;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Kernel;

class ContaoKernel extends Kernel implements HttpCacheProvider
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
     * @var JwtManager
     */
    private $jwtManager;

    /**
     * @var ManagerConfig
     */
    private $managerConfig;

    /**
     * @var ContaoCache
     */
    private $httpCache;

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
     *
     * @deprecated since Symfony 4.2, use getProjectDir() instead
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

    public function setPluginLoader(PluginLoader $pluginLoader): void
    {
        $this->pluginLoader = $pluginLoader;
    }

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

    public function setBundleLoader(BundleLoader $bundleLoader): void
    {
        $this->bundleLoader = $bundleLoader;
    }

    public function getJwtManager(): JwtManager
    {
        if (null === $this->jwtManager) {
            $this->jwtManager = new JwtManager($this->getProjectDir());
        }

        return $this->jwtManager;
    }

    public function setJwtManager(JwtManager $jwtManager): void
    {
        $this->jwtManager = $jwtManager;
    }

    public function getManagerConfig(): ManagerConfig
    {
        if (null === $this->managerConfig) {
            $this->managerConfig = new ManagerConfig($this->getProjectDir());
        }

        return $this->managerConfig;
    }

    public function setManagerConfig(ManagerConfig $managerConfig): void
    {
        $this->managerConfig = $managerConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        if ($parametersFile = $this->getConfigFile('parameters.yml')) {
            $loader->load($parametersFile);

            // Set the .env variables from the parameters.yml file (backwards compatibility)
            $loader->load(
                static function (ContainerBuilder $container): void {
                    if (!isset($_SERVER['APP_SECRET']) && $container->hasParameter('secret')) {
                        $container->setParameter('env(APP_SECRET)', $container->getParameter('secret'));
                    }

                    if (!isset($_SERVER['DATABASE_URL']) && $container->hasParameter('database_host')) {
                        $container->setParameter(
                            'env(DATABASE_URL)',
                            sprintf(
                                'mysql://%s:%s@%s:%s/%s',
                                rawurlencode($container->getParameter('database_user')),
                                rawurlencode($container->getParameter('database_password')),
                                rawurlencode($container->getParameter('database_host')),
                                (int) $container->getParameter('database_port'),
                                rawurlencode($container->getParameter('database_name'))
                            )
                        );
                    }

                    if (!isset($_SERVER['MAILER_URL']) && $container->hasParameter('mailer_transport')) {
                        if ('sendmail' === $container->getParameter('mailer_transport')) {
                            $container->setParameter('env(MAILER_URL)', 'sendmail://localhost');
                        } elseif ('smtp' === $container->getParameter('mailer_transport')) {
                            $parameters = [];

                            if ($username = $container->getParameter('mailer_user')) {
                                $parameters[] = 'username='.rawurlencode($container->getParameter('mailer_user'));
                            }

                            if ($username = $container->getParameter('mailer_password')) {
                                $parameters[] = 'password='.rawurlencode($container->getParameter('mailer_password'));
                            }

                            if ($username = $container->getParameter('mailer_encryption')) {
                                $parameters[] = 'encryption='.rawurlencode($container->getParameter('mailer_encryption'));
                            }

                            $append = '';

                            if (!empty($parameters)) {
                                $append = '?'.implode('&', $parameters);
                            }

                            $container->setParameter(
                                'env(MAILER_URL)',
                                sprintf(
                                    'smtp://%s:%s%s',
                                    rawurlencode($container->getParameter('mailer_host')),
                                    (int) $container->getParameter('mailer_port'),
                                    $append
                                )
                            );
                        }
                    }
                }
            );
        }

        $config = $this->getManagerConfig()->all();
        $plugins = $this->getPluginLoader()->getInstancesOf(PluginLoader::CONFIG_PLUGINS);

        /** @var ConfigPluginInterface[] $plugins */
        foreach ($plugins as $plugin) {
            $plugin->registerContainerConfiguration($loader, $config);
        }

        // Reload the parameters.yml file
        if ($parametersFile) {
            $loader->load($parametersFile);
        }

        if ($configFile = $this->getConfigFile('config_'.$this->getEnvironment().'.yml')) {
            $loader->load($configFile);
        } elseif ($configFile = $this->getConfigFile('config.yml')) {
            $loader->load($configFile);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getHttpCache(): ContaoCache
    {
        if (null !== $this->httpCache) {
            return $this->httpCache;
        }

        return $this->httpCache = new ContaoCache($this, $this->getProjectDir().'/var/cache/prod/http_cache');
    }

    /**
     * Sets the project directory (the Contao kernel does not know its location).
     */
    public static function setProjectDir(string $projectDir): void
    {
        self::$projectDir = realpath($projectDir) ?: $projectDir;
    }

    /**
     * @return ContaoKernel|ContaoCache
     */
    public static function fromRequest(string $projectDir, Request $request): HttpKernelInterface
    {
        self::loadEnv($projectDir);

        // See https://github.com/symfony/recipes/blob/master/symfony/framework-bundle/4.2/public/index.php
        if ($trustedProxies = $_SERVER['TRUSTED_PROXIES'] ?? null) {
            Request::setTrustedProxies(explode(',', $trustedProxies), Request::HEADER_X_FORWARDED_ALL ^ Request::HEADER_X_FORWARDED_HOST);
        }

        if ($trustedHosts = $_SERVER['TRUSTED_HOSTS'] ?? null) {
            Request::setTrustedHosts(explode(',', $trustedHosts));
        }

        Request::enableHttpMethodParameterOverride();

        $env = null;
        $parseJwt = !isset($_SERVER['APP_ENV']) && !isset($_SERVER['SYMFONY_ENV']);
        $jwtManager = null;

        if ($parseJwt) {
            $jwtManager = new JwtManager($projectDir);
            $jwt = $jwtManager->parseRequest($request);

            if (\is_array($jwt) && $jwt['debug'] ?? false) {
                $env = 'dev';
            }
        }

        $kernel = static::create($projectDir, $env);

        if ($parseJwt) {
            $kernel->setJwtManager($jwtManager);
        }

        if (!$kernel->isDebug()) {
            $cache = $kernel->getHttpCache();

            // Enable the Symfony reverse proxy if request has no surrogate capability
            if (null !== $cache->getSurrogate() && !$cache->getSurrogate()->hasSurrogateCapability($request)) {
                return $cache;
            }
        }

        return $kernel;
    }

    public static function fromInput(string $projectDir, InputInterface $input): self
    {
        self::loadEnv($projectDir);

        return static::create($projectDir, $input->getParameterOption(['--env', '-e'], null));
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

        if (null === ($container = $this->getContainer())) {
            return;
        }

        // Set the plugin loader again so it is available at runtime (synthetic service)
        $container->set('contao_manager.plugin_loader', $this->getPluginLoader());

        // Set the JWT manager only if the debug mode has not been configured in env variables
        if (!isset($_SERVER['APP_ENV']) && !isset($_SERVER['SYMFONY_ENV'])) {
            $container->set('contao_manager.jwt_manager', $this->getJwtManager());
        }
    }

    private function getConfigFile(string $file): ?string
    {
        $rootDir = $this->getProjectDir();

        if (file_exists($rootDir.'/config/'.$file)) {
            if ('parameters.yml' === $file) {
                @trigger_error('Using a parameters.yml file has been deprecated and will no longer work in Contao 5.0. Use an .env file instead.', E_USER_DEPRECATED);
            }

            return $rootDir.'/config/'.$file;
        }

        // Fallback to the legacy config file (see #566)
        if (file_exists($rootDir.'/app/config/'.$file)) {
            @trigger_error(sprintf('Storing the "%s" file in the "app/config" folder has been deprecated and will no longer work in Contao 5.0. Move it to the "config" folder instead.', $file), E_USER_DEPRECATED);

            return $rootDir.'/app/config/'.$file;
        }

        return null;
    }

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
        $appBundle = AppBundle::class;

        if (!isset($bundles[$appBundle]) && class_exists($appBundle)) {
            $bundles[$appBundle] = new $appBundle();
        }
    }

    private static function create(string $projectDir, string $env = null): self
    {
        if (null === $env) {
            $env = (string) ($_SERVER['APP_ENV'] ?? $_SERVER['SYMFONY_ENV'] ?? 'prod');
        }

        if ('dev' !== $env && 'prod' !== $env) {
            throw new \RuntimeException('The Contao Managed Edition only supports the "dev" and "prod" environments');
        }

        Plugin::autoloadModules($projectDir.'/system/modules');
        static::setProjectDir($projectDir);

        if ('dev' === $env) {
            Debug::enable();
        }

        return new static($env, 'dev' === $env);
    }

    private static function loadEnv(string $projectDir): void
    {
        $varName = isset($_SERVER['SYMFONY_ENV']) ? 'SYMFONY_ENV' : 'APP_ENV';

        // Do not load .env files if they are already loaded or actual env variables are used
        if (isset($_SERVER[$varName])) {
            return;
        }

        if (!file_exists($projectDir.'/.env')) {
            return;
        }

        $dotEnv = new Dotenv(false);
        $dotEnv->loadEnv($projectDir.'/.env', $varName, 'prod', []);
    }
}
