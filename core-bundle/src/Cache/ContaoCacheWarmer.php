<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Cache;

use Contao\CoreBundle\Config\Dumper\CombinedFileDumper;
use Contao\CoreBundle\Config\Loader\PhpFileLoader;
use Contao\CoreBundle\Config\Loader\XliffFileLoader;
use Contao\CoreBundle\Config\ResourceFinderInterface;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
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
    private $rootDir;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    /**
     * @param Filesystem               $filesystem
     * @param ResourceFinderInterface  $finder
     * @param FileLocator              $locator
     * @param string                   $rootDir
     * @param Connection               $connection
     * @param ContaoFrameworkInterface $framework
     */
    public function __construct(Filesystem $filesystem, ResourceFinderInterface $finder, FileLocator $locator, string $rootDir, Connection $connection, ContaoFrameworkInterface $framework)
    {
        $this->filesystem = $filesystem;
        $this->finder = $finder;
        $this->locator = $locator;
        $this->rootDir = $rootDir;
        $this->connection = $connection;
        $this->framework = $framework;
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function isOptional(): bool
    {
        return true;
    }

    /**
     * Generates the config cache.
     *
     * @param string $cacheDir
     */
    private function generateConfigCache(string $cacheDir): void
    {
        $dumper = new CombinedFileDumper($this->filesystem, new PhpFileLoader(), $cacheDir.'/contao', true);

        foreach (['autoload.php', 'config.php'] as $file) {
            $files = $this->findConfigFiles($file);

            if (!empty($files)) {
                $dumper->dump($files, 'config/'.$file, ['type' => 'namespaced']);
            }
        }
    }

    /**
     * Generates the DCA cache.
     *
     * @param string $cacheDir
     */
    private function generateDcaCache(string $cacheDir): void
    {
        $dumper = new CombinedFileDumper($this->filesystem, new PhpFileLoader(), $cacheDir.'/contao', true);
        $processed = [];
        $files = $this->findDcaFiles();

        foreach ($files as $file) {
            if (in_array($file->getBasename(), $processed, true)) {
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

    /**
     * Generates the language cache.
     *
     * @param string $cacheDir
     */
    private function generateLanguageCache(string $cacheDir): void
    {
        $dumper = new CombinedFileDumper(
            $this->filesystem,
            new DelegatingLoader(new LoaderResolver([new PhpFileLoader(), new XliffFileLoader($this->rootDir)])),
            $cacheDir.'/contao'
        );

        $dumper->setHeader("<?php\n");

        foreach ($this->getLanguagesInUse() as $language) {
            $processed = [];
            $files = $this->findLanguageFiles($language);

            foreach ($files as $file) {
                $name = substr($file->getBasename(), 0, -4);

                if (in_array($name, $processed, true)) {
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

    /**
     * Generates the DCA extracts.
     *
     * @param string $cacheDir
     */
    private function generateDcaExtracts(string $cacheDir): void
    {
        $processed = [];
        $files = $this->findDcaFiles();

        foreach ($files as $file) {
            if (in_array($file->getBasename(), $processed, true)) {
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

    /**
     * Generates the template mapper array.
     *
     * @param string $cacheDir The cache directory
     */
    private function generateTemplateMapper(string $cacheDir): void
    {
        $files = $this->findTemplateFiles();

        if (empty($files)) {
            return;
        }

        $mapper = [];

        foreach ($files as $file) {
            $mapper[$file->getBasename('.html5')] = rtrim(
                $this->filesystem->makePathRelative($file->getPath(), $this->rootDir),
                '/'
            );
        }

        $this->filesystem->dumpFile(
            $cacheDir.'/contao/config/templates.php',
            sprintf("<?php\n\nreturn %s;\n", var_export($mapper, true))
        );
    }

    /**
     * Returns the languages which are currently in use.
     *
     * @return array
     */
    private function getLanguagesInUse(): array
    {
        // Get all languages in use (see #6013)
        $query = "
            SELECT language FROM tl_member
            UNION SELECT language FROM tl_user
            UNION SELECT REPLACE(language, '-', '_') FROM tl_page
            WHERE type='root'
        ";

        $statement = $this->connection->prepare($query);
        $statement->execute();

        $languages = [];

        while (false !== ($language = $statement->fetch(\PDO::FETCH_OBJ))) {
            if ('' === $language->language) {
                continue;
            }

            $languages[] = $language->language;

            // Also cache "de" if "de-CH" is requested
            if (strlen($language->language) > 2) {
                $languages[] = substr($language->language, 0, 2);
            }
        }

        return array_unique($languages);
    }

    /**
     * Checks if the installation is complete.
     *
     * @return bool
     */
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
     * Returns the config files.
     *
     * @param string $name
     *
     * @return string|array
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
     * Returns the DCA files.
     *
     * @return Finder|SplFileInfo[]|array
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
     * Returns the language files.
     *
     * @param string $language
     *
     * @return Finder|SplFileInfo[]|array
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
     * Returns the template files.
     *
     * @return Finder|SplFileInfo[]|array
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
