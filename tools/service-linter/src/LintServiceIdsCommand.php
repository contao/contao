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
use Contao\CoreBundle\Csrf\MemoryTokenStorage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

class LintServiceIdsCommand extends Command
{
    protected static $defaultName = 'contao:lint-service-ids';

    protected static $defaultDescription = 'Checks the Contao service IDs.';

    /**
     * Strip from name if the alias is part of the namespace.
     */
    private static array $aliasNames = [
        'subscriber' => 'listener',
    ];

    private static array $renameNamespaces = [
        'authentication' => '',
        'contao_core' => 'contao',
        'event_listener' => 'listener',
        'http_kernel' => '',
        'util' => '',
    ];

    /**
     * Strip these prefixes from the last chunk of the service ID.
     */
    private static array $stripPrefixes = [
        'contao_table_',
        'core_',
    ];

    /**
     * Classes that are not meant to be a single service and can therefore not
     * derive the service ID from the class name.
     */
    private static array $generalServiceClasses = [
        ResourceFinder::class,
        MemoryTokenStorage::class,
    ];

    private static array $exceptions = [
        'contao.listener.menu.backend',
        'contao.migration.version_400.version_400_update',
    ];

    public function __construct(public string $projectDir)
    {
        parent::__construct();
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
            $yaml = Yaml::parseFile($file->getPathname(), Yaml::PARSE_CUSTOM_TAGS);

            if (!isset($yaml['services'])) {
                continue;
            }

            $serviceIds = [];

            foreach ($yaml['services'] as $serviceId => $config) {
                if ('_' === $serviceId[0]) {
                    continue;
                }

                if (
                    !\is_string($config) // autowiring aliases
                    && !isset($config['alias'])
                    && str_contains((string) $serviceId, '\\')
                    && !str_ends_with($serviceId, 'Controller')
                ) {
                    $hasError = true;

                    $io->warning(sprintf(
                        'The %s service defined in the %s file uses a FQCN as service ID, which is only allowed for controllers.',
                        $serviceId,
                        $file->getRelativePathname(),
                    ));
                }

                if (!isset($config['class'])) {
                    continue;
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
                        'The %s service defined in the %s file should have the ID "%s" but has the ID "%s".',
                        $config['class'],
                        $file->getRelativePathname(),
                        $id,
                        $serviceId,
                    ));
                }
            }

            $sortedIds = $serviceIds;
            usort($sortedIds, 'strnatcasecmp');
            $sortedIds = array_values($sortedIds);

            if ($serviceIds !== $sortedIds) {
                $hasError = true;

                $io->warning(sprintf('The services in the %s file are not sorted correctly.', $file->getRelativePathname()));
                $io->writeln((new \Diff($serviceIds, $sortedIds))->render(new \Diff_Renderer_Text_Unified()));
            }
        }

        if ($hasError) {
            return 1;
        }

        $io->success('All service IDs are correct.');

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
                $name = substr($name, \strlen((string) $prefix));
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
