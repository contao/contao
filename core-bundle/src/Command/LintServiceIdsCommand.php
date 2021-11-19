<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;
use Webmozart\PathUtil\Path;

class LintServiceIdsCommand extends Command
{
    protected static $defaultName = 'contao:lint-service-ids';

    private static array $ignore = [
        'listener',
        'migration',
        'picker',
        'runtime',
        'subscriber',
    ];

    private static array $ignoredChunks = [
        'authentication',
        'candidates',
        'data_collector',
        'http_kernel',
        'insert_tag',
        'logout',
        'matcher',
        'remember_me',
        'schema',
        'voter',
    ];

    private static array $exceptions = [
        'contao.assets.assets_context',
        'contao.assets.files_context',
        'contao.csrf.token_storage',
        'contao.fragment.forward_renderer',
        'contao.image.deferred_image_storage',
        'contao.listener.element_template_options',
        'contao.listener.module_template_options',
        'contao.migration.version400.version400_update',
        'contao.monolog.handler',
        'contao.monolog.processor',
        'contao.resource_finder',
        'contao.response_context.accessor',
        'contao.response_context.factory',
        'contao.routing.candidates',
        'contao.routing.input_enhancer',
        'contao.routing.page_registry',
        'contao.security.authentication_listener',
        'contao.security.authentication_provider',
        'contao.security.backend_user_provider',
        'contao.security.entry_point',
        'contao.security.frontend_user_provider',
        'contao.security.token_checker',
        'contao.security.user_checker',
        'contao.session.contao_backend',
        'contao.session.contao_frontend',
    ];

    private string $projectDir;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Checks the Contao service IDs.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $files = Finder::create()
            ->files()
            ->name('*.yml')
            ->path('-bundle/src/Resources/config')
            ->in(Path::join($this->projectDir, 'vendor/contao/contao'))
        ;

        $tc = 0;
        $io = new SymfonyStyle($input, $output);

        foreach ($files as $file) {
            $fc = 0;
            $yaml = Yaml::parseFile($file->getPathname(), Yaml::PARSE_CUSTOM_TAGS);

            if (!isset($yaml['services'])) {
                continue;
            }

            foreach ($yaml['services'] as $serviceId => $config) {
                if ('_' === $serviceId[0] || !isset($config['class'])) {
                    continue;
                }

                if (\in_array($serviceId, self::$exceptions, true)) {
                    continue;
                }

                if (($id = $this->getServiceIdFromClass($config['class'])) && $id !== $serviceId) {
                    ++$fc;
                    ++$tc;
                    $io->warning(sprintf('The %s service should have the ID "%s" but has the ID "%s".', $config['class'], $id, $serviceId));
                }
            }

            if ($fc > 0) {
                $io->error(sprintf('%d wrong service IDs in the %s file.', $fc, $file->getRelativePathname()));
            } else {
                $io->success(sprintf('All service IDs are correct in the %s file.', $file->getRelativePathname()));
            }
        }

        if ($tc > 0) {
            $io->error(sprintf('%d wrong service IDs in all files.', $tc));
        }

        return 0;
    }

    private function getServiceIdFromClass(string $class): ?string
    {
        $chunks = explode('\\', Container::underscore($class));

        foreach ($chunks as &$chunk) {
            $chunk = preg_replace('(^([a-z]+)(\d+)(.*)$)', '$1_$2$3', $chunk);
        }

        unset($chunk);

        // The first chunk is the vendor name (e.g. Contao).
        $vendor = array_shift($chunks);

        if ('contao' !== $vendor) {
            return null;
        }

        // The second chunk is the bundle name (e.g. CoreBundle).
        $bundle = array_shift($chunks);

        if ('_bundle' !== substr($bundle, -7)) {
            return null;
        }

        $bundle = substr($bundle, 0, -7);

        // The last chunk is the class name
        $name = array_pop($chunks);

        // The remaining chunks make up the sub-namespaces between the bundle
        // and the class name. We ignore the ones in self::$ignoredChunks.
        foreach ($chunks as $i => &$chunk) {
            if ('event_listener' === $chunk) {
                $chunk = 'listener';
            }

            if (\in_array($chunk, self::$ignoredChunks, true)) {
                unset($chunks[$i]);
            }
        }

        unset($chunk);

        // The first remaining chunk is our category.
        $category = array_shift($chunks);

        // Now we split up the class name to unset certain chunks of the path,
        // e.g. we remove "Listener" from "BackendMenuListener".
        $nameChunks = explode('_', $name);

        foreach ($nameChunks as $i => $nameChunk) {
            if (
                'contao' === $nameChunk
                || $category === $nameChunk
                || \in_array($nameChunk, $chunks, true)
                || \in_array($nameChunk, self::$ignore, true)
            ) {
                unset($nameChunks[$i]);
            }
        }

        $name = implode('_', $nameChunks);
        $path = \count($chunks) ? implode('.', $chunks) : '';
        $prefix = strtolower($vendor.'_'.$bundle);

        if ('contao_core' === $prefix) {
            $prefix = 'contao';
        }

        if ($category === $name) {
            $category = '';
        }

        return implode('.', array_filter([$prefix, $category, $path, $name]));
    }
}
