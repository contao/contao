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

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerBundle\ContaoManager\ApiCommand\GenerateJwtCookieCommand;
use Contao\ManagerBundle\ContaoManager\ApiCommand\GetConfigCommand;
use Contao\ManagerBundle\ContaoManager\ApiCommand\GetDotEnvCommand;
use Contao\ManagerBundle\ContaoManager\ApiCommand\ParseJwtCookieCommand;
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

/**
 * @internal
 */
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

    public function getPackageDependencies(): array
    {
        return ['contao/core-bundle'];
    }

    public function getBundles(ParserInterface $parser): array
    {
        $configs = [
            BundleConfig::create(FrameworkBundle::class),
            BundleConfig::create(SecurityBundle::class)->setLoadAfter([FrameworkBundle::class]),
            BundleConfig::create(TwigBundle::class),
            BundleConfig::create(MonologBundle::class),
            BundleConfig::create(SwiftmailerBundle::class),
            BundleConfig::create(DoctrineBundle::class),
            BundleConfig::create(LexikMaintenanceBundle::class),
            BundleConfig::create(NelmioCorsBundle::class),
            BundleConfig::create(NelmioSecurityBundle::class),
            BundleConfig::create(FOSHttpCacheBundle::class),
            BundleConfig::create(ContaoManagerBundle::class)->setLoadAfter([ContaoCoreBundle::class]),
            BundleConfig::create(DebugBundle::class)->setLoadInProduction(false),
            BundleConfig::create(WebProfilerBundle::class)->setLoadInProduction(false),
        ];

        // Autoload the legacy modules
        if (null !== static::$autoloadModules && file_exists(static::$autoloadModules)) {
            /** @var array<SplFileInfo> $modules */
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

    public function registerContainerConfiguration(LoaderInterface $loader, array $managerConfig): void
    {
        $loader->load(
            static function (ContainerBuilder $container) use ($loader): void {
                if ('dev' === $container->getParameter('kernel.environment')) {
                    $loader->load('@ContaoManagerBundle/Resources/skeleton/config/config_dev.yml');
                } else {
                    $loader->load('@ContaoManagerBundle/Resources/skeleton/config/config_prod.yml');
                }

                $container->setParameter('container.dumper.inline_class_loader', true);
            }
        );
    }

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
            static function (RouteCollection $carry, RouteCollection $item): RouteCollection {
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

    public function getApiFeatures(): array
    {
        return [
            'dot-env' => [
                'APP_SECRET',
                'APP_ENV',
                'COOKIE_WHITELIST',
                'DATABASE_URL',
                'DISABLE_HTTP_CACHE',
                'MAILER_URL',
                'TRACE_LEVEL',
                'TRUSTED_PROXIES',
                'TRUSTED_HOSTS',
            ],
            'config' => [
                'disable-packages',
            ],
            'jwt-cookie' => [
                'debug',
            ],
        ];
    }

    public function getApiCommands(): array
    {
        return [
            GetConfigCommand::class,
            SetConfigCommand::class,
            GetDotEnvCommand::class,
            SetDotEnvCommand::class,
            RemoveDotEnvCommand::class,
            GenerateJwtCookieCommand::class,
            ParseJwtCookieCommand::class,
        ];
    }

    public function getExtensionConfig($extensionName, array $extensionConfigs, PluginContainerBuilder $container): array
    {
        switch ($extensionName) {
            case 'contao':
                return $this->handlePrependLocale($extensionConfigs, $container);

            case 'framework':
                if (!isset($_SERVER['APP_SECRET'])) {
                    $container->setParameter('env(APP_SECRET)', $container->getParameter('secret'));
                }

                return $extensionConfigs;

            case 'doctrine':
                if (!isset($_SERVER['DATABASE_URL'])) {
                    $container->setParameter('env(DATABASE_URL)', $this->getDatabaseUrl($container, $extensionConfigs));
                }

                return $this->addDefaultPdoDriverOptions($extensionConfigs, $container);

            case 'swiftmailer':
                $extensionConfigs = $this->checkMailerTransport($extensionConfigs, $container);

                if (!isset($_SERVER['MAILER_URL'])) {
                    $container->setParameter('env(MAILER_URL)', $this->getMailerUrl($container));
                }

                return $extensionConfigs;

            case 'nelmio_security':
                return $this->checkClickJackingPaths($extensionConfigs);
        }

        return $extensionConfigs;
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
     * Sets the PDO driver options if applicable (#2459).
     *
     * @return array<string,array<string,array<string,array<string,mixed>>>>
     */
    private function addDefaultPdoDriverOptions(array $extensionConfigs, ContainerBuilder $container): array
    {
        // Do not add PDO options if the constant does not exist
        if (!\defined('PDO::MYSQL_ATTR_MULTI_STATEMENTS')) {
            return $extensionConfigs;
        }

        $driver = null;
        $url = null;

        foreach ($extensionConfigs as $extensionConfig) {
            // Do not add PDO options if custom options have been defined
            // Since this is merged recursively, we don't need to check other configs
            if (isset($extensionConfig['dbal']['connections']['default']['options'][\PDO::MYSQL_ATTR_MULTI_STATEMENTS])) {
                return $extensionConfigs;
            }

            if (isset($extensionConfig['dbal']['connections']['default']['driver'])) {
                $driver = $extensionConfig['dbal']['connections']['default']['driver'];
            }

            if (isset($extensionConfig['dbal']['connections']['default']['url'])) {
                $url = $container->resolveEnvPlaceholders($extensionConfig['dbal']['connections']['default']['url'], true);
            }
        }

        // If URL is set it overrides the driver option
        if (null !== $url) {
            $driver = str_replace('-', '_', parse_url($url, PHP_URL_SCHEME));
        }

        // Do not add PDO options if the selected driver is not mysql
        if (null !== $driver && !\in_array($driver, ['pdo_mysql', 'mysql', 'mysql2'], true)) {
            return $extensionConfigs;
        }

        $extensionConfigs[] = [
            'dbal' => [
                'connections' => [
                    'default' => [
                        'options' => [
                            \PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,
                        ],
                    ],
                ],
            ],
        ];

        return $extensionConfigs;
    }

    /**
     * Changes the mail transport from "mail" to "sendmail".
     *
     * @return array<string,array<string,array<string,array<string,mixed>>>>
     */
    private function checkMailerTransport(array $extensionConfigs, ContainerBuilder $container): array
    {
        if ('mail' === $container->getParameter('mailer_transport')) {
            $container->setParameter('mailer_transport', 'sendmail');
        }

        return $extensionConfigs;
    }

    /**
     * Adds a click jacking configuration for "^/.*" if not already defined.
     *
     * @return array<string,array<string,array<string,array<string,mixed>>>>
     */
    private function checkClickJackingPaths(array $extensionConfigs): array
    {
        foreach ($extensionConfigs as $extensionConfig) {
            if (isset($extensionConfig['clickjacking']['paths']['^/.*'])) {
                return $extensionConfigs;
            }
        }

        $extensionConfigs[] = [
            'clickjacking' => [
                'paths' => [
                    '^/.*' => 'SAMEORIGIN',
                ],
            ],
        ];

        return $extensionConfigs;
    }

    private function getDatabaseUrl(ContainerBuilder $container, array $extensionConfigs): string
    {
        $driver = 'mysql';

        foreach ($extensionConfigs as $extensionConfig) {
            // Loop over all configs so the last one wins
            $driver = $extensionConfig['dbal']['connections']['default']['driver'] ?? $driver;
        }

        $userPassword = '';

        if ($user = $container->getParameter('database_user')) {
            $userPassword = $this->encodeUrlParameter((string) $user);

            if ($password = $container->getParameter('database_password')) {
                $userPassword .= ':'.$this->encodeUrlParameter((string) $password);
            }

            $userPassword .= '@';
        }

        $dbName = '';

        if ($name = $container->getParameter('database_name')) {
            $dbName = '/'.$this->encodeUrlParameter((string) $name);
        }

        return sprintf(
            '%s://%s%s:%s%s',
            str_replace('_', '-', $driver),
            $userPassword,
            $container->getParameter('database_host'),
            (int) $container->getParameter('database_port'),
            $dbName
        );
    }

    private function getMailerUrl(ContainerBuilder $container): string
    {
        if ('sendmail' === $container->getParameter('mailer_transport')) {
            return 'sendmail://localhost';
        }

        $parameters = [];

        if ($user = $container->getParameter('mailer_user')) {
            $parameters[] = 'username='.$this->encodeUrlParameter((string) $user);

            if ($password = $container->getParameter('mailer_password')) {
                $parameters[] = 'password='.$this->encodeUrlParameter((string) $password);
            }
        }

        if ($encryption = $container->getParameter('mailer_encryption')) {
            $parameters[] = 'encryption='.$this->encodeUrlParameter((string) $encryption);
        }

        $qs = '';

        if (!empty($parameters)) {
            $qs = '?'.implode('&', $parameters);
        }

        return sprintf(
            'smtp://%s:%s%s',
            $container->getParameter('mailer_host'),
            (int) $container->getParameter('mailer_port'),
            $qs
        );
    }

    private function encodeUrlParameter(string $parameter): string
    {
        return str_replace('%', '%%', rawurlencode($parameter));
    }
}
