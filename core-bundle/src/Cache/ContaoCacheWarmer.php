<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Cache;

use Contao\CoreBundle\Config\Dumper\CombinedFileDumper;
use Contao\CoreBundle\Config\Loader\PhpFileLoader;
use Contao\CoreBundle\Config\Loader\XliffFileLoader;
use Contao\CoreBundle\Config\ResourceFinderInterface;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Intl\Locales;
use Contao\DcaExtractor;
use Contao\Model;
use Doctrine\DBAL\Connection;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class ContaoCacheWarmer implements CacheWarmerInterface
{
    private array $locales;

    /**
     * @internal Do not inherit from this class; decorate the "contao.cache.warmer" service instead
     */
    public function __construct(
        private Filesystem $filesystem,
        private ResourceFinderInterface $finder,
        private FileLocator $locator,
        private string $projectDir,
        private Connection $connection,
        private ContaoFramework $framework,
        Locales $locales,
    ) {
        $this->locales = $locales->getEnabledLocaleIds();
    }

    public function warmUp(string $cacheDir): array
    {
        if (!$this->isCompleteInstallation()) {
            return [];
        }

        $this->framework->initialize();

        $this->generateConfigCache($cacheDir);
        $this->generateDcaCache($cacheDir);
        $this->generateLanguageCache($cacheDir);
        $this->generateDcaExtracts($cacheDir);
        $this->generateTemplateMapper($cacheDir);
        $this->generateColumnCastTypes($cacheDir);

        return [];
    }

    public function isOptional(): bool
    {
        return true;
    }

    private function generateConfigCache(string $cacheDir): void
    {
        $dumper = new CombinedFileDumper($this->filesystem, new PhpFileLoader(), Path::join($cacheDir, 'contao'));

        foreach (['autoload.php', 'config.php'] as $file) {
            $files = $this->findConfigFiles($file);

            if (!empty($files)) {
                $dumper->dump($files, Path::join('config', $file), ['type' => 'namespaced']);
            }
        }
    }

    private function generateDcaCache(string $cacheDir): void
    {
        $dumper = new CombinedFileDumper($this->filesystem, new PhpFileLoader(), Path::join($cacheDir, 'contao'));

        $processed = [];
        $files = $this->findDcaFiles();

        foreach ($files as $file) {
            $baseName = $file->getBasename();

            if (\in_array($baseName, $processed, true)) {
                continue;
            }

            $processed[] = $baseName;
            $path = Path::join('dca', $baseName);

            $dumper->dump($this->locator->locate($path, null, false), $path, ['type' => 'namespaced']);
        }
    }

    private function generateLanguageCache(string $cacheDir): void
    {
        $dumper = new CombinedFileDumper(
            $this->filesystem,
            new DelegatingLoader(new LoaderResolver([new PhpFileLoader(), new XliffFileLoader($this->projectDir)])),
            Path::join($cacheDir, 'contao')
        );

        $dumper->setHeader("<?php\n");

        foreach ($this->locales as $language) {
            $processed = [];
            $files = $this->findLanguageFiles($language);

            foreach ($files as $file) {
                $name = substr($file->getBasename(), 0, -4);

                if (\in_array($name, $processed, true)) {
                    continue;
                }

                $processed[] = $name;

                $subfiles = $this->finder
                    ->findIn(Path::join('languages', $language))
                    ->files()
                    ->name("/^$name\\.(php|xlf)$/")
                ;

                try {
                    $dumper->dump(
                        iterator_to_array($subfiles),
                        Path::join('languages', $language, "$name.php"),
                        ['type' => $language]
                    );
                } catch (\OutOfBoundsException) {
                    continue;
                }
            }
        }
    }

    private function generateDcaExtracts(string $cacheDir): void
    {
        $processed = [];
        $files = $this->findDcaFiles();

        foreach ($files as $file) {
            if (\in_array($file->getBasename(), $processed, true)) {
                continue;
            }

            $processed[] = $file->getBasename();

            $table = $file->getBasename('.php');
            $extract = DcaExtractor::getInstance($table);

            if (!$extract->isDbTable()) {
                continue;
            }

            $this->filesystem->dumpFile(
                Path::join($cacheDir, 'contao/sql', "$table.php"),
                sprintf(
                    "<?php\n\n%s\n\n%s\n\n%s\n\n%s\n\n%s\n\n\$this->blnIsDbTable = true;\n",
                    sprintf('$this->arrMeta = %s;', var_export($extract->getMeta(), true)),
                    sprintf('$this->arrFields = %s;', var_export($extract->getFields(), true)),
                    sprintf('$this->arrUniqueFields = %s;', var_export($extract->getUniqueFields(), true)),
                    sprintf('$this->arrKeys = %s;', var_export($extract->getKeys(), true)),
                    sprintf('$this->arrRelations = %s;', var_export($extract->getRelations(), true))
                )
            );
        }
    }

    private function generateTemplateMapper(string $cacheDir): void
    {
        $files = $this->findTemplateFiles();

        if (empty($files)) {
            return;
        }

        $mapper = [];

        foreach ($files as $file) {
            $mapper[$file->getBasename('.html5')] = Path::makeRelative($file->getPath(), $this->projectDir);
        }

        $this->filesystem->dumpFile(
            Path::join($cacheDir, 'contao/config/templates.php'),
            sprintf("<?php\n\nreturn %s;\n", var_export($mapper, true))
        );
    }

    private function generateColumnCastTypes(string $cacheDir): void
    {
        $this->filesystem->dumpFile(
            Path::join($cacheDir, 'contao/config/column-types.php'),
            sprintf("<?php\n\nreturn %s;\n", var_export(Model::getColumnCastTypes(false), true))
        );
    }

    private function isCompleteInstallation(): bool
    {
        try {
            $this->connection->executeQuery('SELECT COUNT(*) FROM tl_page');
        } catch (\Exception) {
            return false;
        }

        return true;
    }

    /**
     * @return array<string>|string
     */
    private function findConfigFiles(string $name): array|string
    {
        try {
            return $this->locator->locate(Path::join('config', $name), null, false);
        } catch (\InvalidArgumentException) {
            return [];
        }
    }

    /**
     * @return Finder|array<SplFileInfo>
     */
    private function findDcaFiles(): Finder|array
    {
        try {
            return $this->finder->findIn('dca')->files()->name('*.php');
        } catch (\InvalidArgumentException) {
            return [];
        }
    }

    /**
     * @return Finder|array<SplFileInfo>
     */
    private function findLanguageFiles(string $language): Finder|array
    {
        try {
            return $this->finder
                ->findIn(Path::join('languages', $language))
                ->files()
                ->name('/\.(php|xlf)$/')
            ;
        } catch (\InvalidArgumentException) {
            return [];
        }
    }

    /**
     * @return Finder|array<SplFileInfo>
     */
    private function findTemplateFiles(): Finder|array
    {
        try {
            return $this->finder->findIn('templates')->name('*.html5');
        } catch (\InvalidArgumentException) {
            return [];
        }
    }
}
