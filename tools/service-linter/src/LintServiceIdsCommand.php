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
use Contao\CoreBundle\DependencyInjection\Attribute\AsBlockInsertTag;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCronJob;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag;
use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTagFlag;
use Contao\CoreBundle\DependencyInjection\Attribute\AsPage;
use Contao\CoreBundle\DependencyInjection\Attribute\AsPickerProvider;
use Contao\CoreBundle\Migration\MigrationInterface;
use Contao\CoreBundle\Picker\PickerProviderInterface;
use Contao\CoreBundle\Routing\Content\ContentUrlResolverInterface;
use Contao\CoreBundle\Search\Indexer\IndexerInterface;
use Monolog\Processor\ProcessorInterface;
use Symfony\Bridge\Monolog\Processor\WebProcessor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Routing\RouteLoaderInterface;
use Symfony\Bundle\MakerBundle\MakerInterface;
use Symfony\Component\Asset\PackageInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;
use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Handler\BatchHandlerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Service\ResetInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\ExtensionInterface;
use Twig\Extension\RuntimeExtensionInterface;
use Twig\Loader\LoaderInterface;

class LintServiceIdsCommand extends Command
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
     * Classes that are not meant to be a single service and can therefore not
     * derive the service ID from the class name.
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
    private static array $attributeToTag = [
        AsContentElement::class => 'contao.content_element',
        AsFrontendModule::class => 'contao.frontend_module',
        AsPage::class => 'contao.page',
        AsPickerProvider::class => 'contao.picker_provider',
        AsCronJob::class => 'contao.cronjob',
        AsHook::class => 'contao.hook',
        AsCallback::class => 'contao.callback',
        AsInsertTag::class => 'contao.insert_tag',
        AsBlockInsertTag::class => 'contao.block_insert_tag',
        AsInsertTagFlag::class => 'contao.insert_tag_flag',
        AsCommand::class => 'console.command',
        AsEventListener::class => 'kernel.event_listener',
        AsController::class => 'controller.service_arguments',
        AsMessageHandler::class => 'messenger.message_handler',
        AsTargetedValueResolver::class => 'controller.targeted_value_resolver',
    ];

    /**
     * @var array<string>
     */
    private static array $parentClassToTag = [
        PackageInterface::class => 'assets.package',
        Command::class => 'console.command',
        ServiceLocator::class => 'container.service_locator',
        AbstractController::class => 'controller.service_arguments',
        PickerProviderInterface::class => 'contao.picker_provider',
        MigrationInterface::class => 'contao.migration',
        ContentUrlResolverInterface::class => 'contao.content_url_resolver',
        IndexerInterface::class => 'contao.search_indexer',
        EscargotSubscriberInterface::class => 'contao.escargot_subscriber',
        ServiceSubscriberInterface::class => 'container.service_subscriber',
        ValueResolverInterface::class => 'controller.argument_value_resolver',
        DataCollectorInterface::class => 'data_collector',
        CacheClearerInterface::class => 'kernel.cache_clearer',
        CacheWarmerInterface::class => 'kernel.cache_warmer',
        EventDispatcherInterface::class => 'event_dispatcher.dispatcher',
        EventSubscriberInterface::class => 'kernel.event_subscriber',
        ResetInterface::class => 'kernel.reset',
        MessageHandlerInterface::class => 'messenger.message_handler',
        BatchHandlerInterface::class => 'messenger.message_handler',
        TransportFactoryInterface::class => 'messenger.transport_factory',
        RouteLoaderInterface::class => 'routing.route_loader',
        MakerInterface::class => 'maker.command',
        ProcessorInterface::class => 'monolog.processor',
        WebProcessor::class => 'monolog.processor',
        VoterInterface::class => 'security.voter',
        ExtensionInterface::class => 'twig.extension',
        LoaderInterface::class => 'twig.loader',
        RuntimeExtensionInterface::class => 'twig.runtime',
    ];

    public function __construct(private readonly string $projectDir)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('contao:lint-service-ids')
            ->setDescription('Checks the Contao service IDs.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
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

        $hasError = false;
        $io = new SymfonyStyle($input, $output);

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

                // The same service ID is used for two different classes (e.g. contao.routing.candidates).
                if ($classesByServiceId[$serviceId] !== $config['class']) {
                    $ignoreClasses[] = $config['class'];
                    $ignoreClasses[] = $classesByServiceId[$serviceId];
                }

                // The same class is used for two different services (e.g. ArrayAttributeBag).
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
                    $hasError = true;

                    $io->warning(sprintf(
                        'The %s service defined in the %s file uses a FQCN as service ID, which is only allowed for controllers.',
                        $serviceId,
                        $fileName,
                    ));
                }

                if (!isset($config['class'])) {
                    continue;
                }

                if (true !== ($config['autoconfigure'] ?? null)) {
                    $tags = [];

                    if (isset($config['tags'])) {
                        foreach ($config['tags'] as $tag) {
                            if (\is_string($tag)) {
                                $tags[$tag] = true;
                            } elseif (!isset($tag['name'])) {
                                $key = array_key_first($tag);
                                $tags[$key] = $tag[$key];
                            } else {
                                if (1 === \count($tag)) {
                                    $hasError = true;

                                    $io->warning(sprintf(
                                        'The "%s" tag of the "%s" service defined in the %s file should not be an array.',
                                        $tag['name'],
                                        $serviceId,
                                        $fileName,
                                    ));
                                } else {
                                    $attrs = [];

                                    foreach ($tag as $k => $v) {
                                        if ('name' !== $k) {
                                            $attrs[] = "$k: $v";
                                        }
                                    }

                                    $hasError = true;

                                    $io->warning(sprintf(
                                        'The tag of the "%s" service defined in the %s file should be "%s".',
                                        $serviceId,
                                        $fileName,
                                        $tag['name'].': { '.implode(', ', $attrs).' }',
                                    ));
                                }

                                $tags[$tag['name']] = $tag;
                            }
                        }
                    }

                    $r = new \ReflectionClass($config['class']);

                    foreach (self::$parentClassToTag as $class => $tag) {
                        if (!isset($tags[$tag]) && $r->isSubclassOf($class)) {
                            $hasError = true;

                            $io->warning(sprintf(
                                'The "%s" service defined in the %s file should have a "%s" tag.',
                                $serviceId,
                                $fileName,
                                $tag,
                            ));
                        }
                    }

                    $attributes = $r->getAttributes();

                    foreach ($attributes as $attribute) {
                        $name = $attribute->getName();

                        if (!isset(self::$attributeToTag[$name])) {
                            continue;
                        }

                        $hasError = true;

                        $io->warning(sprintf(
                            'The "%s" service defined in the %s file should have a "%s" tag instead of the #[%s] attribute.',
                            $serviceId,
                            $fileName,
                            self::$attributeToTag[$name],
                            (new \ReflectionClass($name))->getShortName(),
                        ));
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
                    $hasError = true;

                    $io->warning(sprintf(
                        'The "%s" service defined in the %s file should have the ID "%s".',
                        $serviceId,
                        $fileName,
                        $id,
                    ));
                }
            }

            $sortedIds = $serviceIds;
            usort($sortedIds, 'strnatcasecmp');
            $sortedIds = array_values($sortedIds);

            if ($serviceIds !== $sortedIds) {
                $hasError = true;

                $io->warning(sprintf('The services in the %s file are not sorted correctly.', $fileName));
                $io->writeln((new \Diff($serviceIds, $sortedIds))->render(new \Diff_Renderer_Text_Unified()));
            }
        }

        if ($hasError) {
            return 1;
        }

        $io->success('All service definitions are correct.');

        return 0;
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

        // The remaining chunks make up the sub-namespaces between the bundle
        // and the class name. We rename the ones from self::$renameNamespaces.
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

        // Now we split up the class name to unset certain chunks of the path,
        // e.g. we remove "Listener" from "BackendMenuListener".
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
}
