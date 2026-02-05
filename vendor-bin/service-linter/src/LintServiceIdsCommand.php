<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Tools\ServiceLinter;

use Contao\CoreBundle\Config\ResourceFinder;
use Contao\CoreBundle\Crawl\Escargot\Subscriber\EscargotSubscriberInterface;
use Contao\CoreBundle\Csrf\MemoryTokenStorage;
use Contao\CoreBundle\Migration\MigrationInterface;
use Contao\CoreBundle\Picker\PickerProviderInterface;
use Contao\CoreBundle\Routing\Content\ContentUrlResolverInterface;
use Contao\CoreBundle\Search\Indexer\IndexerInterface;
use Monolog\Processor\ProcessorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Routing\RouteLoaderInterface;
use Symfony\Component\Asset\PackageInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\Messenger\Handler\BatchHandlerInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Service\ResetInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\ExtensionInterface;
use Twig\Extension\RuntimeExtensionInterface;
use Twig\Loader\LoaderInterface;

#[AsCommand(
    name: 'contao:lint-service-ids',
    description: 'Checks the Contao service IDs.',
)]
class LintServiceIdsCommand
{
    /**
     * Strip from name if the alias is part of the namespace.
     *
     * @var array<string, string>
     */
    private static array $aliasNames = [
        'subscriber' => 'listener',
    ];

    /**
     * @var array<string, string>
     */
    private static array $renameNamespaces = [
        'authentication' => '',
        'contao_core' => 'contao',
        'event_listener' => 'listener',
        'http_kernel' => '',
        'util' => '',
    ];

    /**
     * Strip these prefixes from the last chunk of the service ID.
     *
     * @var array<string>
     */
    private static array $stripPrefixes = [
        'contao_table_',
        'core_',
    ];

    /**
     * Classes that are not meant to be a single service and can therefore not derive
     * the service ID from the class name.
     *
     * @var array<string>
     */
    private static array $generalServiceClasses = [
        ResourceFinder::class,
        MemoryTokenStorage::class,
    ];

    /**
     * @var array<string>
     */
    private static array $exceptions = [
        'contao.listener.menu.backend',
        'contao.migration.version_400.version_400_update',
    ];

    /**
     * @var array<string>
     */
    private static array $tagToAttribute = [
        'contao.content_element' => '#[AsContentElement]',
        'contao.frontend_module' => '#[AsFrontendModule]',
        'contao.page' => '#[AsPage]',
        'contao.picker_provider' => '#[AsPickerProvider]',
        'contao.cronjob' => '#[AsCronJob]',
        'contao.hook' => '#[AsHook]',
        'contao.callback' => '#[AsCallback]',
        'contao.insert_tag' => '#[AsInsertTag]',
        'contao.block_insert_tag' => '#[AsBlockInsertTag]',
        'contao.insert_tag_flag' => '#[AsInsertTagFlag]',
        'console.command' => '#[AsCommand]',
        'kernel.event_listener' => '#[AsEventListener]',
        'controller.service_arguments' => '#[AsController]',
        'messenger.message_handler' => '#[AsMessageHandler]',
        'controller.targeted_value_resolver' => '#[AsTargetedValueResolver]',
    ];

    /**
     * @var array<string>
     */
    private static array $tagToParentClass = [
        'container.service_locator' => ServiceLocator::class,
        'controller.service_arguments' => AbstractController::class,
    ];

    /**
     * @var array<string>
     */
    private static array $tagToInterface = [
        'contao.migration' => MigrationInterface::class,
        'contao.picker_provider' => PickerProviderInterface::class,
        'contao.content_url_resolver' => ContentUrlResolverInterface::class,
        'contao.search_indexer' => IndexerInterface::class,
        'contao.escargot_subscriber' => EscargotSubscriberInterface::class,
        'assets.package' => PackageInterface::class,
        'container.service_subscriber' => ServiceSubscriberInterface::class,
        'controller.argument_value_resolver' => ValueResolverInterface::class,
        'data_collector' => DataCollectorInterface::class,
        'kernel.cache_clearer' => CacheClearerInterface::class,
        'kernel.cache_warmer' => CacheWarmerInterface::class,
        'event_dispatcher.dispatcher' => EventDispatcherInterface::class,
        'kernel.event_subscriber' => EventSubscriberInterface::class,
        'kernel.reset' => ResetInterface::class,
        'messenger.message_handler' => BatchHandlerInterface::class,
        'messenger.transport_factory' => TransportFactoryInterface::class,
        'routing.route_loader' => RouteLoaderInterface::class,
        'monolog.processor' => ProcessorInterface::class,
        'security.voter' => VoterInterface::class,
        'twig.extension' => ExtensionInterface::class,
        'twig.loader' => LoaderInterface::class,
        'twig.runtime' => RuntimeExtensionInterface::class,
    ];

    private SymfonyStyle $io;

    private bool $hasError = false;

    public function __construct(private readonly string $projectDir)
    {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $this->io = $io;

        $files = Finder::create()
            ->files()
            ->name('*.yaml')
            ->name('*.yml')
            ->in([
                $this->projectDir.'/calendar-bundle/config',
                $this->projectDir.'/comments-bundle/config',
                $this->projectDir.'/core-bundle/config',
                $this->projectDir.'/faq-bundle/config',
                $this->projectDir.'/maker-bundle/config',
                $this->projectDir.'/manager-bundle/config',
                $this->projectDir.'/news-bundle/config',
                $this->projectDir.'/newsletter-bundle/config',
            ])
        ;

        $allClasses = [];
        $ignoreClasses = self::$generalServiceClasses;
        $classesByServiceId = [];

        foreach ($files as $file) {
            $yaml = Yaml::parseFile($file->getPathname(), Yaml::PARSE_CUSTOM_TAGS);

            foreach ($yaml['services'] ?? [] as $serviceId => $config) {
                if (!isset($config['class'])) {
                    continue;
                }

                $classesByServiceId[$serviceId] ??= $config['class'];

                // The service ID is used for two different classes, e.g. contao.routing.candidates.
                if ($classesByServiceId[$serviceId] !== $config['class']) {
                    $ignoreClasses[] = $config['class'];
                    $ignoreClasses[] = $classesByServiceId[$serviceId];
                }

                // The class is used for two different services, e.g. ArrayAttributeBag.
                if (\in_array($config['class'], $allClasses, true)) {
                    $ignoreClasses[] = $config['class'];
                }

                $allClasses[] = $config['class'];
            }
        }

        foreach ($files as $file) {
            /** @var array{services: array<string, array>} $yaml */
            $yaml = Yaml::parseFile($file->getPathname(), Yaml::PARSE_CUSTOM_TAGS);

            if (!isset($yaml['services'])) {
                continue;
            }

            $fileName = Path::makeRelative($file->getPathname(), $this->projectDir);
            $serviceIds = [];

            foreach ($yaml['services'] as $serviceId => $config) {
                if ('_' === $serviceId[0]) {
                    continue;
                }

                if (
                    !\is_string($config) // autowiring aliases
                    && !isset($config['alias'])
                    && str_contains($serviceId, '\\')
                    && !str_ends_with($serviceId, 'Controller')
                ) {
                    $this->error('The %s service defined in the %s file uses a FQCN as service ID, which is only allowed for controllers.', $serviceId, $fileName);
                }

                if (!isset($config['class'])) {
                    continue;
                }

                if (isset($config['tags']) && false !== ($config['autoconfigure'] ?? null)) {
                    foreach ($config['tags'] as $tag) {
                        $tagName = \is_array($tag) ? $tag['name'] : $tag;

                        if (isset(self::$tagToParentClass[$tagName])) {
                            $this->error('The "%s" service defined in the %s file has a "%s" tag but should extend the %s class instead.', $serviceId, $fileName, $tagName, self::$tagToInterface[$tagName]);
                        } elseif (isset(self::$tagToInterface[$tagName])) {
                            if (\count($tag) > 1) {
                                continue;
                            }

                            $this->error('The "%s" service defined in the %s file has a "%s" tag but should implement the %s interface instead.', $serviceId, $fileName, $tagName, self::$tagToInterface[$tagName]);
                        } elseif (isset(self::$tagToAttribute[$tagName])) {
                            if ($this->isExternal($serviceId, $config)) {
                                continue;
                            }

                            $this->error('The "%s" service defined in the %s file has a "%s" tag but should use the %s attribute instead.', $serviceId, $fileName, $tagName, self::$tagToAttribute[$tagName]);
                        }
                    }
                }

                if (!isset($config['deprecated'])) {
                    $serviceIds[] = $serviceId;
                }

                if (\in_array($config['class'], $ignoreClasses, true)) {
                    continue;
                }

                if (\in_array($serviceId, self::$exceptions, true)) {
                    continue;
                }

                if (($id = $this->getServiceIdFromClass($config['class'])) && $id !== $serviceId) {
                    $this->error('The "%s" service defined in the %s file should have the ID "%s".', $serviceId, $fileName, $id);
                }
            }

            $sortedIds = $serviceIds;
            usort($sortedIds, strnatcasecmp(...));
            $sortedIds = array_values($sortedIds);

            if ($serviceIds !== $sortedIds) {
                $this->hasError = true;

                $this->error('The services in the %s file are not sorted correctly.', $fileName);
                $this->io->writeln((new \Diff($serviceIds, $sortedIds))->render(new \Diff_Renderer_Text_Unified()));
            }
        }

        if ($this->hasError) {
            return 1;
        }

        $this->io->success('All service definitions are correct.');

        return 0;
    }

    private function error(string $message, mixed ...$args): void
    {
        $this->hasError = true;
        $this->io->warning(\sprintf($message, ...$args));
    }

    private function getServiceIdFromClass(string $class): string|null
    {
        $chunks = explode('\\', strtolower(Container::underscore($class)));

        foreach ($chunks as &$chunk) {
            $chunk = preg_replace('(^([a-z]+)(\d+)(.*)$)', '$1_$2$3', $chunk);
        }

        unset($chunk);

        // The first chunk is the vendor name (e.g. Contao).
        if ('contao' !== array_shift($chunks)) {
            return null;
        }

        // The second chunk is the bundle name (e.g. CoreBundle).
        if (!str_ends_with($chunks[0], '_bundle')) {
            return null;
        }

        // Rename "xxx_bundle" to "contao_xxx"
        $chunks[0] = 'contao_'.substr($chunks[0], 0, -7);

        // The last chunk is the class name.
        $name = array_pop($chunks);

        // The remaining chunks make up the sub-namespaces between the bundle and the
        // class name. We rename the ones from self::$renameNamespaces.
        foreach ($chunks as $i => &$chunk) {
            $chunk = self::$renameNamespaces[$chunk] ?? $chunk;

            if (!$chunk || ($i > 1 && str_contains($name, (string) $chunk))) {
                unset($chunks[$i]);
            }
        }

        unset($chunk);

        // Strip prefixes from the name.
        foreach (self::$stripPrefixes as $prefix) {
            if (str_starts_with($name, $prefix)) {
                $name = substr($name, \strlen($prefix));
            }
        }

        // Now we split up the class name to unset certain chunks of the path, e.g. we
        // remove "Listener" from "BackendMenuListener".
        $nameChunks = explode('_', $name);

        foreach ($nameChunks as $i => $nameChunk) {
            if (
                'contao' === $nameChunk
                || \in_array($nameChunk, $chunks, true)
                || \in_array(self::$aliasNames[$nameChunk] ?? '', $chunks, true)
            ) {
                unset($nameChunks[$i]);
            }

            if (\in_array($nameChunk.'_'.($nameChunks[$i + 1] ?? ''), $chunks, true)) {
                unset($nameChunks[$i], $nameChunks[$i + 1]);
            }
        }

        $name = implode('_', $nameChunks);
        $path = implode('.', $chunks);

        return implode('.', array_filter([$path, $name]));
    }

    private function isExternal(string $serviceId, array $config): bool
    {
        [$namespace] = explode('.', $serviceId);
        $chunks = explode('_', $namespace);

        if (1 === \count($chunks) && 'contao' === $chunks[0]) {
            $chunks = ['contao', 'core'];
        }

        $needle = ucfirst($chunks[0]).'\\'.ucfirst($chunks[1]).'Bundle\\';

        return !str_starts_with($config['class'], $needle);
    }
}
