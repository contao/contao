<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\ContaoManager;

use Contao\ManagerBundle\ContaoManager\ApiCommand\GetConfigCommand;
use Contao\ManagerBundle\ContaoManager\ApiCommand\GetDotEnvCommand;
use Contao\ManagerBundle\ContaoManager\ApiCommand\RemoveDotEnvCommand;
use Contao\ManagerBundle\ContaoManager\ApiCommand\SetConfigCommand;
use Contao\ManagerBundle\ContaoManager\ApiCommand\SetDotEnvCommand;
use Contao\ManagerBundle\ContaoManagerBundle;
use Contao\ManagerPlugin\Api\ApiPluginInterface;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Config\ConfigPluginInterface;
use Contao\ManagerPlugin\Config\ContainerBuilder as PluginContainerBuilder;
use Contao\ManagerPlugin\Config\ExtensionPluginInterface;
use Contao\ManagerPlugin\Dependency\DependentPluginInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\DoctrineCacheBundle\DoctrineCacheBundle;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\DriverException;
use FOS\HttpCacheBundle\FOSHttpCacheBundle;
use Lexik\Bundle\MaintenanceBundle\LexikMaintenanceBundle;
use Nelmio\CorsBundle\NelmioCorsBundle;
use Nelmio\SecurityBundle\NelmioSecurityBundle;
use Symfony\Bundle\DebugBundle\DebugBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class Plugin implements BundlePluginInterface, ConfigPluginInterface, RoutingPluginInterface, ExtensionPluginInterface, DependentPluginInterface, ApiPluginInterface
{
    /**
     * @var string|null
     */
    private static $autoloadModules;

    /**
     * Sets the path to enable autoloading of legacy Contao modules.
     */
    public static function autoloadModules(string $modulePath): void
    {
        static::$autoloadModules = $modulePath;
    }

    /**
     * {@inheritdoc}
     */
    public function getPackageDependencies()
    {
        return ['contao/core-bundle'];
    }

    /**
     * {@inheritdoc}
     */
    public function getBundles(ParserInterface $parser)
    {
        $configs = [
            BundleConfig::create(FrameworkBundle::class),
            BundleConfig::create(SecurityBundle::class),
            BundleConfig::create(TwigBundle::class),
            BundleConfig::create(MonologBundle::class),
            BundleConfig::create(SwiftmailerBundle::class),
            BundleConfig::create(DoctrineBundle::class),
            BundleConfig::create(DoctrineCacheBundle::class),
            BundleConfig::create(LexikMaintenanceBundle::class),
            BundleConfig::create(NelmioCorsBundle::class),
            BundleConfig::create(NelmioSecurityBundle::class),
            BundleConfig::create(FOSHttpCacheBundle::class),
            BundleConfig::create(ContaoManagerBundle::class),
            BundleConfig::create(DebugBundle::class)->setLoadInProduction(false),
            BundleConfig::create(WebProfilerBundle::class)->setLoadInProduction(false),
        ];

        // Autoload the legacy modules
        if (null !== static::$autoloadModules && file_exists(static::$autoloadModules)) {
            /** @var SplFileInfo[] $modules */
            $modules = Finder::create()
                ->directories()
                ->depth(0)
                ->in(static::$autoloadModules)
            ;

            $iniConfigs = [];

            foreach ($modules as $module) {
                if (!file_exists($module->getPathname().'/.skip')) {
                    $iniConfigs[] = $parser->parse($module->getFilename(), 'ini');
                }
            }

            if (!empty($iniConfigs)) {
                $configs = array_merge($configs, ...$iniConfigs);
            }
        }

        return $configs;
    }

    /**
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(LoaderInterface $loader, array $managerConfig): void
    {
        $loader->load(
            function (ContainerBuilder $container) use ($loader): void {
                if ('dev' === $container->getParameter('kernel.environment')) {
                    $loader->load('@ContaoManagerBundle/Resources/skeleton/app/config_dev.yml');
                } else {
                    $loader->load('@ContaoManagerBundle/Resources/skeleton/app/config_prod.yml');
                }

                $container->setParameter('container.autowiring.strict_mode', true);
                $container->setParameter('container.dumper.inline_class_loader', true);
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel): ?RouteCollection
    {
        if ('dev' !== $kernel->getEnvironment()) {
            return null;
        }

        $collections = [];

        $files = [
            '_wdt' => '@WebProfilerBundle/Resources/config/routing/wdt.xml',
            '_profiler' => '@WebProfilerBundle/Resources/config/routing/profiler.xml',
        ];

        foreach ($files as $prefix => $file) {
            /** @var RouteCollection $collection */
            $collection = $resolver->resolve($file)->load($file);
            $collection->addPrefix($prefix);

            $collections[] = $collection;
        }

        $collection = array_reduce(
            $collections,
            function (RouteCollection $carry, RouteCollection $item): RouteCollection {
                $carry->addCollection($item);

                return $carry;
            },
            new RouteCollection()
        );

        // Redirect the deprecated install.php file
        $collection->add(
            'contao_install_redirect',
            new Route(
                '/install.php',
                [
                    '_scope' => 'backend',
                    '_controller' => 'Symfony\Bundle\FrameworkBundle\Controller\RedirectController::redirectAction',
                    'route' => 'contao_install',
                    'permanent' => true,
                ]
            )
        );

        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function getApiFeatures(): array
    {
        return [
            'dot-env' => [
                'APP_DEV_ACCESSKEY',
                'TRUSTED_PROXIES',
                'TRUSTED_HOSTS',
                'DISABLE_HTTP_CACHE',
            ],
            'config' => [
                'disable-packages',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getApiCommands(): array
    {
        return [
            GetConfigCommand::class,
            SetConfigCommand::class,
            GetDotEnvCommand::class,
            SetDotEnvCommand::class,
            RemoveDotEnvCommand::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensionConfig($extensionName, array $extensionConfigs, PluginContainerBuilder $container): array
    {
        switch ($extensionName) {
            case 'contao':
                return $this->handlePrependLocale($extensionConfigs, $container);

            case 'doctrine':
                return $this->addDefaultServerVersion($extensionConfigs, $container);

            default:
                return $extensionConfigs;
        }
    }

    /**
     * Adds backwards compatibility for the %prepend_locale% parameter.
     *
     * @return array<string,array<string,mixed>>
     */
    private function handlePrependLocale(array $extensionConfigs, ContainerBuilder $container): array
    {
        if (!$container->hasParameter('prepend_locale')) {
            return $extensionConfigs;
        }

        foreach ($extensionConfigs as $extensionConfig) {
            if (isset($extensionConfig['prepend_locale'])) {
                return $extensionConfigs;
            }
        }

        @trigger_error('Defining the "prepend_locale" parameter in the parameters.yml file has been deprecated and will no longer work in Contao 5.0. Define the "contao.prepend_locale" parameter in the config.yml file instead.', E_USER_DEPRECATED);

        $extensionConfigs[] = [
            'prepend_locale' => '%prepend_locale%',
        ];

        return $extensionConfigs;
    }

    /**
     * Adds the database server version to the Doctrine DBAL configuration.
     *
     * @return array<string,array<string,array<string,array<string,mixed>>>>
     */
    private function addDefaultServerVersion(array $extensionConfigs, ContainerBuilder $container): array
    {
        $params = [];

        foreach ($extensionConfigs as $extensionConfig) {
            if (isset($extensionConfig['dbal']['connections']['default'])) {
                $params[] = $extensionConfig['dbal']['connections']['default'];
            }
        }

        if (!empty($params)) {
            $params = array_merge(...$params);
        }

        $parameterBag = $container->getParameterBag();

        foreach ($params as $key => $value) {
            $params[$key] = $parameterBag->resolveValue($value);
        }

        // If there are no DB credentials yet (install tool), we have to set
        // the server version to prevent a DBAL exception (see #1422)
        try {
            $connection = DriverManager::getConnection($params);
            $connection->connect();
            $connection->close();
        } catch (DriverException $e) {
            $extensionConfigs[] = [
                'dbal' => [
                    'connections' => [
                        'default' => [
                            'server_version' => '5.5',
                        ],
                    ],
                ],
            ];
        }

        return $extensionConfigs;
    }
}
