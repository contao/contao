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
use Webmozart\PathUtil\Path;

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
     * @var callable
     */
    private $dbalConnectionFactory;

    public function __construct(callable $dbalConnectionFactory = null)
    {
        $this->dbalConnectionFactory = $dbalConnectionFactory ?: [DriverManager::class, 'getConnection'];
    }

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

    public function getBundles(ParserInterface $parser)
    {
        $configs = [
            BundleConfig::create(FrameworkBundle::class),
            BundleConfig::create(SecurityBundle::class)->setLoadAfter([FrameworkBundle::class]),
            BundleConfig::create(TwigBundle::class),
            BundleConfig::create(MonologBundle::class),
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
                if (!file_exists(Path::join($module->getPathname(), '.skip'))) {
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
                $extensionConfigs = $this->checkMailerTransport($extensionConfigs, $container);
                $extensionConfigs = $this->addDefaultMailer($extensionConfigs, $container);

                if (!isset($_SERVER['APP_SECRET'])) {
                    $container->setParameter('env(APP_SECRET)', $container->getParameter('secret'));
                }

                if (!isset($_SERVER['MAILER_DSN'])) {
                    if (isset($_SERVER['MAILER_URL'])) {
                        $container->setParameter('env(MAILER_DSN)', $this->getMailerDsnFromMailerUrl($_SERVER['MAILER_URL']));
                    } else {
                        $container->setParameter('env(MAILER_DSN)', $this->getMailerDsn($container));
                    }
                }

                return $extensionConfigs;

            case 'doctrine':
                if (!isset($_SERVER['DATABASE_URL'])) {
                    $container->setParameter('env(DATABASE_URL)', $this->getDatabaseUrl($container));
                }

                return $this->addDefaultServerVersion($extensionConfigs, $container);
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

        trigger_deprecation('contao/manager-bundle', '4.6', 'Defining the "prepend_locale" parameter in the parameters.yml file has been deprecated and will no longer work in Contao 5.0. Define the "contao.prepend_locale" parameter in the config.yml file instead.');

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
            $params[$key] = $parameterBag->unescapeValue($container->resolveEnvPlaceholders($value, true));
        }

        // If there are no DB credentials yet (install tool), we have to set
        // the server version to prevent a DBAL exception (see #1422)
        try {
            $connection = \call_user_func($this->dbalConnectionFactory, $params);
            $connection->connect();
            $connection->query('SHOW TABLES');
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
     * Dynamically adds a default mailer to the config, if no mailer is defined.
     *
     * We cannot add a default mailer configuration to the skeleton config.yml,
     * since different types of configurations are not allowed.
     *
     * For example, if the Manager Bundle defined
     *
     *     framework:
     *         mailer:
     *             dsn: '%env(MAILER_DSN)%'
     *
     * in the skeleton config.yml and the user adds
     *
     *     framework:
     *         mailer:
     *             transports:
     *                 foobar: 'smtps://smtp.example.com'
     *
     * to their config.yml, the merged configuration will lead to an error, since
     * you cannot use "framework.mailer.dsn" together with "framework.mailer.transports".
     * Thus the default mailer configuration needs to be added dynamically, if
     * not already present.
     *
     * @return array<string,array<string,array<string,array<string,mixed>>>>
     */
    private function addDefaultMailer(array $extensionConfigs, ContainerBuilder $container): array
    {
        foreach ($extensionConfigs as $config) {
            if (isset($config['mailer']) && (isset($config['mailer']['transports']) || $config['mailer']['dsn'])) {
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

    private function getDatabaseUrl(ContainerBuilder $container): string
    {
        $userPassword = '';

        if ($user = $container->getParameter('database_user')) {
            $userPassword = $this->encodeUrlParameter($user);

            if ($password = $container->getParameter('database_password')) {
                $userPassword .= ':'.$this->encodeUrlParameter($password);
            }

            $userPassword .= '@';
        }

        $dbName = '';

        if ($name = $container->getParameter('database_name')) {
            $dbName = '/'.$this->encodeUrlParameter($name);
        }

        return sprintf(
            'mysql://%s%s:%s%s',
            $userPassword,
            $container->getParameter('database_host'),
            (int) $container->getParameter('database_port'),
            $dbName
        );
    }

    private function getMailerDsnFromMailerUrl(string $mailerUrl): string
    {
        if (false === $parts = parse_url($mailerUrl)) {
            throw new \InvalidArgumentException(sprintf('The MAILER_URL "%s" is not valid.', $mailerUrl));
        }

        $options = [
            'transport' => null,
            'username' => null,
            'password' => null,
            'host' => null,
            'port' => null,
            'encryption' => null,
        ];

        $queryOptions = [];

        if (isset($parts['scheme'])) {
            $options['transport'] = $parts['scheme'];
        }

        if (isset($parts['user'])) {
            $options['username'] = rawurldecode($parts['user']);
        }

        if (isset($parts['pass'])) {
            $options['password'] = rawurldecode($parts['pass']);
        }

        if (isset($parts['host'])) {
            $options['host'] = rawurldecode($parts['host']);
        }

        if (isset($parts['port'])) {
            $options['port'] = $parts['port'];
        }

        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);

            foreach ($query as $key => $value) {
                if (empty($key)) {
                    continue;
                }

                if (\array_key_exists($key, $options)) {
                    $options[$key] = $value;
                } else {
                    $queryOptions[$key] = $value;
                }
            }
        }

        if (empty($options['transport'])) {
            throw new \InvalidArgumentException(sprintf('The MAILER_URL "%s" is not valid.', $mailerUrl));
        }

        if (\in_array($options['transport'], ['mail', 'sendmail'], true)) {
            return 'sendmail+smtp://default';
        }

        /*
         * Check for gmail transport.
         *
         * With Swiftmailer a DSN like "gmail://username:password@localhost" was
         * supported out-of-the-box. See https://symfony.com/doc/4.4/email.html#using-gmail-to-send-emails
         * Symfony Mailer supports something similar, but only with an additional
         * dependency. See https://symfony.com/doc/4.4/components/mailer.html#transport
         *
         * Thus we add backwards compatibility for the "gmail" transport here.
         */
        if ('gmail' === $options['transport']) {
            $options['host'] = 'smtp.gmail.com';
            $options['transport'] = 'smtps';
        }

        if (empty($options['host']) || !\in_array($options['transport'], ['smtp', 'smtps'], true)) {
            throw new \InvalidArgumentException(sprintf('The MAILER_URL "%s" is not valid.', $mailerUrl));
        }

        $transport = $options['transport'];
        $credentials = '';
        $port = '';

        if (!empty($options['encryption']) && 'ssl' === $options['encryption']) {
            $transport = 'smtps';
        }

        if (!empty($options['username'])) {
            $credentials .= $this->encodeUrlParameter($options['username']);

            if (!empty($options['password'])) {
                $credentials .= ':'.$this->encodeUrlParameter($options['password']);
            }

            $credentials .= '@';
        }

        if (!empty($options['port'])) {
            $port = ':'.$options['port'];
        }

        return sprintf(
            '%s://%s%s%s%s',
            $transport,
            $credentials,
            $options['host'],
            $port,
            !empty($queryOptions) ? '?'.http_build_query($queryOptions) : ''
        );
    }

    private function getMailerDsn(ContainerBuilder $container): string
    {
        if ('sendmail' === $container->getParameter('mailer_transport')) {
            return 'sendmail+smtp://default';
        }

        $transport = 'smtp';
        $credentials = '';
        $portSuffix = '';

        if (($encryption = $container->getParameter('mailer_encryption')) && 'ssl' === $encryption) {
            $transport = 'smtps';
        }

        if ($user = $container->getParameter('mailer_user')) {
            $credentials .= $this->encodeUrlParameter($user);

            if ($password = $container->getParameter('mailer_password')) {
                $credentials .= ':'.$this->encodeUrlParameter($password);
            }

            $credentials .= '@';
        }

        if ($port = $container->getParameter('mailer_port')) {
            $portSuffix = ':'.$port;
        }

        return sprintf(
            '%s://%s%s%s',
            $transport,
            $credentials,
            $container->getParameter('mailer_host'),
            $portSuffix
        );
    }

    private function encodeUrlParameter(string $parameter): string
    {
        return str_replace('%', '%%', rawurlencode($parameter));
    }
}
