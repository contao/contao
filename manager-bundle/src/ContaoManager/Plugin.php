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
use League\FlysystemBundle\FlysystemBundle;
use Nelmio\CorsBundle\NelmioCorsBundle;
use Nelmio\SecurityBundle\NelmioSecurityBundle;
use Symfony\Bundle\DebugBundle\DebugBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Mailer\Transport\NativeTransportFactory;
use Symfony\Component\Routing\RouteCollection;
use Twig\Extra\TwigExtraBundle\TwigExtraBundle;

/**
 * @internal
 */
class Plugin implements BundlePluginInterface, ConfigPluginInterface, RoutingPluginInterface, ExtensionPluginInterface, DependentPluginInterface, ApiPluginInterface
{
    private static string|null $autoloadModules = null;

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
            BundleConfig::create(TwigExtraBundle::class),
            BundleConfig::create(MonologBundle::class),
            BundleConfig::create(DoctrineBundle::class),
            BundleConfig::create(NelmioCorsBundle::class),
            BundleConfig::create(NelmioSecurityBundle::class),
            BundleConfig::create(FOSHttpCacheBundle::class),
            BundleConfig::create(ContaoManagerBundle::class)->setLoadAfter([ContaoCoreBundle::class]),
            BundleConfig::create(DebugBundle::class)->setLoadInProduction(false),
            BundleConfig::create(WebProfilerBundle::class)->setLoadInProduction(false),
            BundleConfig::create(FlysystemBundle::class)->setLoadAfter([ContaoCoreBundle::class]),
        ];

        // Autoload the legacy modules
        if (null !== static::$autoloadModules && file_exists(static::$autoloadModules)) {
            $modules = Finder::create()
                ->directories()
                ->depth(0)
                ->in(static::$autoloadModules)
            ;

            $iniConfigs = [];

            foreach ($modules as $module) {
                if (!file_exists(Path::join($module->getPathname(), '.skip'))) {
                    $iniConfigs[] = $parser->parse($module->getFilename(), 'ini');
                }
            }

            if ($iniConfigs) {
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
                    $loader->load('@ContaoManagerBundle/skeleton/config/config_dev.yaml');
                } else {
                    $loader->load('@ContaoManagerBundle/skeleton/config/config_prod.yaml');
                }

                $container->setParameter('container.dumper.inline_class_loader', true);
            },
        );
    }

    public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel): RouteCollection|null
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

        return array_reduce(
            $collections,
            static function (RouteCollection $carry, RouteCollection $item): RouteCollection {
                $carry->addCollection($item);

                return $carry;
            },
            new RouteCollection(),
        );
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
                'MAILER_DSN',
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
                if (!$container->hasParameter('contao.dns_mapping')) {
                    $container->setParameter('env(DNS_MAPPING)', '[]');
                    $container->setParameter('contao.dns_mapping', '%env(json:DNS_MAPPING)%');
                }

                return $extensionConfigs;

            case 'framework':
                $extensionConfigs = $this->checkMailerTransport($extensionConfigs, $container);
                $extensionConfigs = $this->addDefaultMailer($extensionConfigs);

                if (!isset($_SERVER['APP_SECRET'])) {
                    if ($container->hasParameter('secret')) {
                        $container->setParameter('env(APP_SECRET)', $container->getParameter('secret'));
                    } else {
                        $container->setParameter('env(APP_SECRET)', '');
                    }
                }

                if (!isset($_SERVER['MAILER_DSN'])) {
                    $container->setParameter('env(MAILER_DSN)', $this->getMailerDsn($container));
                }

                return $extensionConfigs;

            case 'doctrine':
                if (!isset($_SERVER['DATABASE_URL'])) {
                    $container->setParameter('env(DATABASE_URL)', $this->getDatabaseUrl($container, $extensionConfigs));
                }

                $extensionConfigs = $this->addDefaultPdoDriverOptions($extensionConfigs, $container);
                $extensionConfigs = $this->addDefaultDoctrineMapping($extensionConfigs, $container);
                $extensionConfigs = $this->enableStrictMode($extensionConfigs, $container);

                return $this->setDefaultCollation($extensionConfigs);

            case 'nelmio_security':
                return $this->checkClickjackingPaths($extensionConfigs);
        }

        return $extensionConfigs;
    }

    /**
     * Sets the PDO driver options if applicable (#2459).
     *
     * @return array<string, array<string, array<string, array<string, mixed>>>>
     */
    private function addDefaultPdoDriverOptions(array $extensionConfigs, ContainerBuilder $container): array
    {
        // Do not add PDO options if the constant does not exist
        if (!\defined('PDO::MYSQL_ATTR_MULTI_STATEMENTS')) {
            return $extensionConfigs;
        }

        [$driver, $options] = $this->parseDbalDriverAndOptions($extensionConfigs, $container);

        // Do not add PDO options if custom options have been defined
        if (isset($options[\PDO::MYSQL_ATTR_MULTI_STATEMENTS])) {
            return $extensionConfigs;
        }

        // Do not add PDO options if the selected driver is not mysql
        if (null !== $driver && 'mysql' !== $driver) {
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
     * Adds a default ORM mapping for the App namespace if none is configured.
     *
     * @return array<string, array<string, array<string, array<string, mixed>>>>
     */
    private function addDefaultDoctrineMapping(array $extensionConfigs, ContainerBuilder $container): array
    {
        $defaultEntityManager = 'default';

        foreach ($extensionConfigs as $config) {
            if (null !== $em = $config['orm']['default_entity_manager'] ?? null) {
                $defaultEntityManager = $em;
            }
        }

        $mappings = [];
        $autoMappingEnabled = false;

        foreach ($extensionConfigs as $config) {
            $mappings[] = $config['orm']['mappings'] ?? [];

            foreach ($config['orm']['entity_managers'] ?? [] as $em) {
                $mappings[] = $em['mappings'] ?? [];
            }

            $autoMappingEnabled |= ($config['orm']['auto_mapping'] ?? false)
                || ($config['orm']['entity_managers'][$defaultEntityManager]['auto_mapping'] ?? false);
        }

        // Skip if auto mapping is not enabled for the default entity manager.
        if (!$autoMappingEnabled) {
            return $extensionConfigs;
        }

        // Skip if a mapping with the name or alias "App" already exists or any
        // mapping already targets "%kernel.project_dir%/src/Entity".
        foreach (array_replace(...$mappings) as $name => $values) {
            if (
                'App' === $name
                || 'App' === ($values['alias'] ?? '')
                || '%kernel.project_dir%/src/Entity' === ($values['dir'] ?? '')
            ) {
                return $extensionConfigs;
            }
        }

        // Skip if the "%kernel.project_dir%/src/Entity" directory does not exist.
        if (!$container->fileExists(Path::join($container->getParameter('kernel.project_dir'), 'src/Entity'))) {
            return $extensionConfigs;
        }

        $extensionConfigs[] = [
            'orm' => [
                'entity_managers' => [
                    $defaultEntityManager => [
                        'mappings' => [
                            'App' => [
                                'dir' => '%kernel.project_dir%/src/Entity',
                                'is_bundle' => false,
                                'prefix' => 'App\Entity',
                                'alias' => 'App',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return $extensionConfigs;
    }

    /**
     * Enables the SQL strict mode for PDO and MySQL drivers.
     *
     * @return array<string, array<string, array<string, array<string, mixed>>>>
     */
    private function enableStrictMode(array $extensionConfigs, ContainerBuilder $container): array
    {
        [$driver, $options] = $this->parseDbalDriverAndOptions($extensionConfigs, $container);

        // Skip if driver is not supported
        if (null === ($key = ['mysql' => 1002, 'mysqli' => 3][$driver] ?? null)) {
            return $extensionConfigs;
        }

        // Skip if init command is already configured
        if (isset($options[$key])) {
            return $extensionConfigs;
        }

        // Enable strict mode
        $extensionConfigs[] = [
            'dbal' => [
                'connections' => [
                    'default' => [
                        'options' => [
                            $key => "SET SESSION sql_mode=CONCAT(@@sql_mode, IF(INSTR(@@sql_mode, 'STRICT_'), '', ',TRADITIONAL'))",
                        ],
                    ],
                ],
            ],
        ];

        return $extensionConfigs;
    }

    /**
     * Sets the "collate" and "collation" options to the same value (see #4798).
     *
     * @return array<string, array<string, array<string, array<string, mixed>>>>
     */
    private function setDefaultCollation(array $extensionConfigs): array
    {
        $defaultCollation = null;

        foreach ($extensionConfigs as $config) {
            $collation = $config['dbal']['connections']['default']['default_table_options']['collation'] ?? $config['dbal']['connections']['default']['default_table_options']['collate'] ?? null;

            if (null !== $collation) {
                $defaultCollation = $collation;
            }
        }

        if (null !== $defaultCollation) {
            $extensionConfigs[] = [
                'dbal' => [
                    'connections' => [
                        'default' => [
                            'default_table_options' => [
                                'collate' => $defaultCollation,
                                'collation' => $defaultCollation,
                            ],
                        ],
                    ],
                ],
            ];
        }

        return $extensionConfigs;
    }

    /**
     * Changes the mail transport from "mail" to "sendmail".
     *
     * @return array<string, array<string, array<string, array<string, mixed>>>>
     */
    private function checkMailerTransport(array $extensionConfigs, ContainerBuilder $container): array
    {
        if ($container->hasParameter('mailer_transport') && 'mail' === $container->getParameter('mailer_transport')) {
            $container->setParameter('mailer_transport', 'sendmail');
        }

        return $extensionConfigs;
    }

    /**
     * Dynamically adds a default mailer to the config, if no mailer is defined.
     *
     * We cannot add a default mailer configuration to the skeleton config.yaml,
     * since different types of configurations are not allowed.
     *
     * For example, if the Manager Bundle defined
     *
     *     framework:
     *         mailer:
     *             dsn: '%env(MAILER_DSN)%'
     *
     * in the skeleton config.yaml and the user adds
     *
     *     framework:
     *         mailer:
     *             transports:
     *                 foobar: 'smtps://smtp.example.com'
     *
     * to their config.yaml, the merged configuration will lead to an error, since
     * you cannot use "framework.mailer.dsn" together with "framework.mailer.transports".
     * Thus, the default mailer configuration needs to be added dynamically if
     * not already present.
     *
     * @return array<string, array<string, array<string, array<string, mixed>>>>
     */
    private function addDefaultMailer(array $extensionConfigs): array
    {
        foreach ($extensionConfigs as $config) {
            if (isset($config['mailer']) && (isset($config['mailer']['transports']) || isset($config['mailer']['dsn']))) {
                return $extensionConfigs;
            }
        }

        $extensionConfigs[] = [
            'mailer' => [
                'dsn' => '%env(MAILER_DSN)%',
            ],
        ];

        return $extensionConfigs;
    }

    /**
     * @return array{0: string|null, 1: array<string, mixed>}
     */
    private function parseDbalDriverAndOptions(array $extensionConfigs, ContainerBuilder $container): array
    {
        $driver = null;
        $url = null;
        $options = [];

        foreach ($extensionConfigs as $config) {
            if (null !== ($driverConfig = $config['dbal']['connections']['default']['driver'] ?? null)) {
                $driver = $driverConfig;
            }

            if (null !== ($urlConfig = $config['dbal']['connections']['default']['url'] ?? null)) {
                $url = $container->resolveEnvPlaceholders($urlConfig, true);
            }

            if (null !== ($optionsConfig = $config['dbal']['connections']['default']['options'] ?? null)) {
                $options[] = $optionsConfig;
            }
        }

        // If URL is set, it overrides the driver option
        if (!empty($url)) {
            $driver = str_replace('-', '_', parse_url($url, PHP_URL_SCHEME));
        }

        // Normalize the driver name
        if (\in_array($driver, ['pdo_mysql', 'mysql2'], true)) {
            $driver = 'mysql';
        }

        return [$driver, array_replace([], ...$options)];
    }

    /**
     * Adds a clickjacking configuration for "^/.*" if not already defined.
     *
     * @return array<string, array<string, array<string, array<string, mixed>>>>
     */
    private function checkClickjackingPaths(array $extensionConfigs): array
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

        if ($container->hasParameter('database_user') && $user = $container->getParameter('database_user')) {
            $userPassword = $this->encodeUrlParameter((string) $user);

            if ($container->hasParameter('database_password') && $password = $container->getParameter('database_password')) {
                $userPassword .= ':'.$this->encodeUrlParameter((string) $password);
            }

            $userPassword .= '@';
        }

        $dbName = '';

        if ($container->hasParameter('database_name') && $name = $container->getParameter('database_name')) {
            $dbName .= '/'.$this->encodeUrlParameter((string) $name);
        }

        if ($container->hasParameter('database_version') && $version = $container->getParameter('database_version')) {
            $dbName .= '?serverVersion='.$this->encodeUrlParameter((string) $version);
        }

        return sprintf(
            '%s://%s%s:%s%s',
            str_replace('_', '-', $driver),
            $userPassword,
            $container->hasParameter('database_host') ? $container->getParameter('database_host') : 'localhost',
            $container->hasParameter('database_port') ? (int) $container->getParameter('database_port') : 3306,
            $dbName,
        );
    }

    private function getMailerDsn(ContainerBuilder $container): string
    {
        if (!$container->hasParameter('mailer_transport') || 'sendmail' === $container->getParameter('mailer_transport')) {
            return class_exists(NativeTransportFactory::class) ? 'native://default' : 'sendmail://default';
        }

        $transport = 'smtp';
        $credentials = '';
        $portSuffix = '';

        if ($container->hasParameter('mailer_encryption') && ($encryption = $container->getParameter('mailer_encryption')) && 'ssl' === $encryption) {
            $transport = 'smtps';
        }

        if ($container->hasParameter('mailer_user') && $user = $container->getParameter('mailer_user')) {
            $credentials .= $this->encodeUrlParameter((string) $user);

            if ($container->hasParameter('mailer_password') && $password = $container->getParameter('mailer_password')) {
                $credentials .= ':'.$this->encodeUrlParameter((string) $password);
            }

            $credentials .= '@';
        }

        if ($port = $container->hasParameter('mailer_port') ? $container->getParameter('mailer_port') : 25) {
            $portSuffix = ':'.$port;
        }

        return sprintf(
            '%s://%s%s%s',
            $transport,
            $credentials,
            $container->hasParameter('mailer_host') ? $container->getParameter('mailer_host') : '127.0.0.1',
            $portSuffix,
        );
    }

    private function encodeUrlParameter(string $parameter): string
    {
        return str_replace('%', '%%', rawurlencode($parameter));
    }
}
