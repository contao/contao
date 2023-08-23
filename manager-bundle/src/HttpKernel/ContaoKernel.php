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
use Contao\ManagerPlugin\HttpKernel\HttpCacheSubscriberPluginInterface;
use Contao\ManagerPlugin\PluginLoader;
use FOS\HttpCache\SymfonyCache\HttpCacheProvider;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Kernel;

class ContaoKernel extends Kernel implements HttpCacheProvider
{
    protected static string|null $projectDir = null;
    private PluginLoader|null $pluginLoader = null;
    private BundleLoader|null $bundleLoader = null;
    private JwtManager|null $jwtManager = null;
    private ManagerConfig|null $managerConfig = null;
    private ContaoCache|null $httpCache = null;

    public function shutdown(): void
    {
        // Reset bundle loader to re-calculate bundle order after cache:clear
        if ($this->booted) {
            $this->bundleLoader = null;
        }

        parent::shutdown();
    }

    public function registerBundles(): array
    {
        $bundles = [];

        $this->addBundlesFromPlugins($bundles);

        return $bundles;
    }

    public function getProjectDir(): string
    {
        if (null === self::$projectDir) {
            throw new \LogicException('ContaoKernel::setProjectDir() must be called to initialize the Contao kernel');
        }

        return self::$projectDir;
    }

    public function getCacheDir(): string
    {
        return Path::join($this->getProjectDir(), 'var/cache', $this->getEnvironment());
    }

    public function getLogDir(): string
    {
        return Path::join($this->getProjectDir(), 'var/logs');
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

    public function getBundleLoader(): BundleLoader
    {
        if (!$this->bundleLoader) {
            $parser = new DelegatingParser();
            $parser->addParser(new JsonParser());
            $parser->addParser(new IniParser(Path::join($this->getProjectDir(), 'system/modules')));

            $this->bundleLoader = new BundleLoader($this->getPluginLoader(), new ConfigResolverFactory(), $parser);
        }

        return $this->bundleLoader;
    }

    public function setBundleLoader(BundleLoader $bundleLoader): void
    {
        $this->bundleLoader = $bundleLoader;
    }

    public function getJwtManager(): JwtManager|null
    {
        return $this->jwtManager;
    }

    public function setJwtManager(JwtManager $jwtManager): void
    {
        $this->jwtManager = $jwtManager;
    }

    public function getManagerConfig(): ManagerConfig
    {
        return $this->managerConfig ??= new ManagerConfig($this->getProjectDir());
    }

    public function setManagerConfig(ManagerConfig $managerConfig): void
    {
        $this->managerConfig = $managerConfig;
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(
            function (ContainerBuilder $container) use ($loader): void {
                if ($parametersFile = $this->getConfigFile('parameters', $container)) {
                    $loader->load($parametersFile);
                }

                $config = $this->getManagerConfig()->all();
                $plugins = $this->getPluginLoader()->getInstancesOf(PluginLoader::CONFIG_PLUGINS);

                /** @var array<ConfigPluginInterface> $plugins */
                foreach ($plugins as $plugin) {
                    $plugin->registerContainerConfiguration($loader, $config);
                }

                // Reload the parameters.yaml file
                if ($parametersFile) {
                    $loader->load($parametersFile);
                }

                if ($configFile = $this->getConfigFile('config_'.$this->getEnvironment(), $container)) {
                    $loader->load($configFile);
                } elseif ($configFile = $this->getConfigFile('config', $container)) {
                    $loader->load($configFile);
                }

                // Automatically load the services.yaml file if it exists
                if ($servicesFile = $this->getConfigFile('services', $container)) {
                    $loader->load($servicesFile);
                }

                if ($container->fileExists(Path::join($this->getProjectDir(), 'src'), false)) {
                    $loader->load(__DIR__.'/../../skeleton/config/services.php');
                }
            },
        );
    }

    public function getHttpCache(): ContaoCache
    {
        if ($this->httpCache) {
            return $this->httpCache;
        }

        $this->httpCache = new ContaoCache($this, Path::join($this->getProjectDir(), 'var/cache/prod/http_cache'));

        /** @var array<HttpCacheSubscriberPluginInterface> $plugins */
        $plugins = $this->getPluginLoader()->getInstancesOf(HttpCacheSubscriberPluginInterface::class);

        foreach ($plugins as $plugin) {
            foreach ($plugin->getHttpCacheSubscribers() as $subscriber) {
                $this->httpCache->addSubscriber($subscriber);
            }
        }

        return $this->httpCache;
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
        self::loadEnv($projectDir, 'jwt');

        if ($trustedHosts = $_SERVER['TRUSTED_HOSTS'] ?? null) {
            Request::setTrustedHosts(explode(',', (string) $trustedHosts));
        }

        if ($trustedProxies = $_SERVER['TRUSTED_PROXIES'] ?? null) {
            $trustedHeaderSet = Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO;

            // If we have a limited list of trusted hosts, we can safely use the X-Forwarded-Host header
            if ($trustedHosts) {
                $trustedHeaderSet |= Request::HEADER_X_FORWARDED_HOST;
            }

            Request::setTrustedProxies(explode(',', (string) $trustedProxies), $trustedHeaderSet);
        }

        Request::enableHttpMethodParameterOverride();

        $jwtManager = null;
        $env = null;
        $parseJwt = 'jwt' === $_SERVER['APP_ENV'];

        if ($parseJwt) {
            $env = 'prod';

            $jwtManager = new JwtManager($projectDir);
            $jwt = $jwtManager->parseRequest($request);

            if (\is_array($jwt) && ($jwt['debug'] ?? false)) {
                $env = 'dev';
            }

            $_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = $env;
        }

        $kernel = static::create($projectDir, $env);

        if ($parseJwt) {
            $kernel->setJwtManager($jwtManager);
        }

        // Enable the Symfony reverse proxy if not disabled explicitly
        if (!($_SERVER['DISABLE_HTTP_CACHE'] ?? null) && !$kernel->isDebug()) {
            return $kernel->getHttpCache();
        }

        return $kernel;
    }

    public static function fromInput(string $projectDir, InputInterface $input): self
    {
        $env = $input->getParameterOption(['--env', '-e'], null);

        self::loadEnv($projectDir, $env ?: 'prod');

        return static::create($projectDir, $env);
    }

    protected function getContainerBuilder(): PluginContainerBuilder
    {
        $container = new PluginContainerBuilder($this->getPluginLoader(), []);
        $container->getParameterBag()->add($this->getKernelParameters());

        return $container;
    }

    protected function initializeContainer(): void
    {
        parent::initializeContainer();

        $container = $this->getContainer();

        // Set the plugin loader again, so it is available at runtime (synthetic service)
        $container->set('contao_manager.plugin_loader', $this->getPluginLoader());

        // Set the JWT manager only if the debug mode has not been configured in env variables
        if ($jwtManager = $this->getJwtManager()) {
            $container->set('contao_manager.jwt_manager', $jwtManager);
        }
    }

    private function getConfigFile(string $file, ContainerBuilder $container): string|null
    {
        $projectDir = $this->getProjectDir();
        $exists = [];

        foreach (['.yaml', '.php', '.xml'] as $ext) {
            if ($container->fileExists($path = Path::join($projectDir, 'config', $file.$ext))) {
                $exists[] = $path;
            }
        }

        if ($container->fileExists($path = Path::join($projectDir, 'config', $file.'.yml'))) {
            trigger_deprecation('contao/manager-bundle', '5.0', sprintf('Using a %s.yml file has been deprecated and will no longer work in Contao 6.0. Use a %s.yaml file instead', $file, $file));
            $exists[] = $path;
        }

        return $exists[0] ?? null;
    }

    private function addBundlesFromPlugins(array &$bundles): void
    {
        $configs = $this->getBundleLoader()->getBundleConfigs(
            'dev' === $this->getEnvironment(),
            $this->debug ? null : Path::join($this->getCacheDir(), 'bundles.map'),
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

    private static function create(string $projectDir, string|null $env = null): self
    {
        $env ??= $_SERVER['APP_ENV'] ?? 'prod';

        if ('dev' !== $env && 'prod' !== $env) {
            throw new \RuntimeException('The Contao Managed Edition only supports the "dev" and "prod" environments');
        }

        Plugin::autoloadModules(Path::join($projectDir, 'system/modules'));
        static::setProjectDir($projectDir);

        if ('dev' === $env) {
            Debug::enable();
        }

        return new static($env, 'dev' === $env);
    }

    private static function loadEnv(string $projectDir, string $defaultEnv = 'prod'): void
    {
        // Load cached env vars if the .env.local.php file exists
        // See https://github.com/symfony/recipes/blob/master/symfony/framework-bundle/4.2/config/bootstrap.php
        if (\is_array($env = @include Path::join($projectDir, '.env.local.php'))) {
            foreach ($env as $k => $v) {
                $_ENV[$k] ??= isset($_SERVER[$k]) && !str_starts_with($k, 'HTTP_') ? $_SERVER[$k] : $v;
            }
        } elseif (file_exists($filePath = Path::join($projectDir, '.env'))) {
            (new Dotenv())->usePutenv(false)->loadEnv($filePath, 'APP_ENV', $defaultEnv);
        }

        $_SERVER += $_ENV;
        $_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = $_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? null ?: $defaultEnv;
    }
}
