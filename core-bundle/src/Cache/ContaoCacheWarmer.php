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
use Contao\DcaExtractor;
use Doctrine\DBAL\Connection;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class ContaoCacheWarmer implements CacheWarmerInterface
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var ResourceFinderInterface
     */
    private $finder;

    /**
     * @var FileLocator
     */
    private $locator;

    /**
     * @var string
     */
    private $projectDir;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * @var array
     */
    private $locales;

    /**
     * @internal Do not inherit from this class; decorate the "contao.cache.warm_internal" service instead
     */
    public function __construct(Filesystem $filesystem, ResourceFinderInterface $finder, FileLocator $locator, string $projectDir, Connection $connection, ContaoFramework $framework, array $locales)
    {
        $this->filesystem = $filesystem;
        $this->finder = $finder;
        $this->locator = $locator;
        $this->projectDir = $projectDir;
        $this->connection = $connection;
        $this->framework = $framework;
        $this->locales = $locales;
    }

    public function warmUp($cacheDir): void
    {
        if (!$this->isCompleteInstallation()) {
            return;
        }

        $this->framework->initialize();

        $this->generateConfigCache($cacheDir);
        $this->generateDcaCache($cacheDir);
        $this->generateLanguageCache($cacheDir);
        $this->generateDcaExtracts($cacheDir);
        $this->generateTemplateMapper($cacheDir);
    }

    public function isOptional(): bool
    {
        return true;
    }

    private function generateConfigCache(string $cacheDir): void
    {
        $dumper = new CombinedFileDumper($this->filesystem, new PhpFileLoader(), $cacheDir.'/contao');

        foreach (['autoload.php', 'config.php'] as $file) {
            $files = $this->findConfigFiles($file);

            if (!empty($files)) {
                $dumper->dump($files, 'config/'.$file, ['type' => 'namespaced']);
            }
        }
    }

    private function generateDcaCache(string $cacheDir): void
    {
        $dumper = new CombinedFileDumper($this->filesystem, new PhpFileLoader(), $cacheDir.'/contao');
        $processed = [];
        $files = $this->findDcaFiles();

        foreach ($files as $file) {
            if (\in_array($file->getBasename(), $processed, true)) {
                continue;
            }

            $processed[] = $file->getBasename();

            $dumper->dump(
                $this->locator->locate('dca/'.$file->getBasename(), null, false),
                'dca/'.$file->getBasename(),
                ['type' => 'namespaced']
            );
        }
    }

    private function generateLanguageCache(string $cacheDir): void
    {
        $dumper = new CombinedFileDumper(
            $this->filesystem,
            new DelegatingLoader(new LoaderResolver([new PhpFileLoader(), new XliffFileLoader($this->projectDir)])),
            $cacheDir.'/contao'
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

                $subfiles = $this->finder->findIn('languages/'.$language)->files()->name('/^'.$name.'\.(php|xlf)$/');

                try {
                    $dumper->dump(
                        iterator_to_array($subfiles),
                        sprintf('languages/%s/%s.php', $language, $name),
                        ['type' => $language]
                    );
                } catch (\OutOfBoundsException $e) {
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
                sprintf('%s/contao/sql/%s.php', $cacheDir, $table),
                sprintf(
                    "<?php\n\n%s\n\n%s\n\n%s\n\n%s\n\n%s\n\n%s\n\n\$this->blnIsDbTable = true;\n",
                    sprintf('$this->arrMeta = %s;', var_export($extract->getMeta(), true)),
                    sprintf('$this->arrFields = %s;', var_export($extract->getFields(), true)),
                    sprintf('$this->arrOrderFields = %s;', var_export($extract->getOrderFields(), true)),
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
            $mapper[$file->getBasename('.html5')] = rtrim(
                $this->filesystem->makePathRelative($file->getPath(), $this->projectDir),
                '/'
            );
        }

        $this->filesystem->dumpFile(
            $cacheDir.'/contao/config/templates.php',
            sprintf("<?php\n\nreturn %s;\n", var_export($mapper, true))
        );
    }

    private function isCompleteInstallation(): bool
    {
        try {
            $this->connection->query('SELECT COUNT(*) FROM tl_page');
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * @return array<string>|string
     */
    private function findConfigFiles(string $name)
    {
        try {
            return $this->locator->locate('config/'.$name, null, false);
        } catch (\InvalidArgumentException $e) {
            return [];
        }
    }

    /**
     * @return Finder|array<SplFileInfo>|array
     */
    private function findDcaFiles()
    {
        try {
            return $this->finder->findIn('dca')->files()->name('*.php');
        } catch (\InvalidArgumentException $e) {
            return [];
        }
    }

    /**
     * @return Finder|array<SplFileInfo>|array
     */
    private function findLanguageFiles(string $language)
    {
        try {
            return $this->finder->findIn('languages/'.$language)->files()->name('/\.(php|xlf)$/');
        } catch (\InvalidArgumentException $e) {
            return [];
        }
    }

    /**
     * @return Finder|array<SplFileInfo>|array
     */
    private function findTemplateFiles()
    {
        try {
            return $this->finder->findIn('templates')->name('*.html5');
        } catch (\InvalidArgumentException $e) {
            return [];
        }
    }
}
