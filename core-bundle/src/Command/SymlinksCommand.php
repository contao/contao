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

use Contao\CoreBundle\Analyzer\HtaccessAnalyzer;
use Contao\CoreBundle\Config\ResourceFinderInterface;
use Contao\CoreBundle\Event\ContaoCoreEvents;
use Contao\CoreBundle\Event\GenerateSymlinksEvent;
use Contao\CoreBundle\Util\SymlinkUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Lock\LockInterface;

/**
 * Symlinks the public resources into the web directory.
 */
class SymlinksCommand extends Command
{
    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * @var array
     */
    private $rows = [];

    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var string
     */
    private $webDir;

    /**
     * @var string
     */
    private $uploadPath;

    /**
     * @var string
     */
    private $logsDir;

    /**
     * @var ResourceFinderInterface
     */
    private $resourceFinder;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var LockInterface
     */
    private $lock;

    /**
     * @var int
     */
    private $statusCode = 0;

    public function __construct(string $rootDir, string $uploadPath, string $logsDir, ResourceFinderInterface $resourceFinder, EventDispatcherInterface $eventDispatcher, LockInterface $lock)
    {
        $this->rootDir = $rootDir;
        $this->uploadPath = $uploadPath;
        $this->logsDir = $logsDir;
        $this->resourceFinder = $resourceFinder;
        $this->eventDispatcher = $eventDispatcher;
        $this->lock = $lock;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('contao:symlinks')
            ->setDefinition([
                new InputArgument('target', InputArgument::OPTIONAL, 'The target directory', 'web'),
            ])
            ->setDescription('Symlinks the public resources into the web directory.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->lock->acquire()) {
            $output->writeln('The command is already running in another process.');

            return 1;
        }

        $this->io = new SymfonyStyle($input, $output);
        $this->webDir = rtrim($input->getArgument('target'), '/');

        $this->generateSymlinks();

        if (!empty($this->rows)) {
            $this->io->newLine();
            $this->io->table(['', 'Symlink', 'Target / Error'], $this->rows);
        }

        $this->lock->release();

        return $this->statusCode;
    }

    /**
     * Generates the symlinks in the web directory.
     */
    private function generateSymlinks(): void
    {
        $fs = new Filesystem();

        // Remove the base folders in the document root
        $fs->remove($this->rootDir.'/'.$this->webDir.'/'.$this->uploadPath);
        $fs->remove($this->rootDir.'/'.$this->webDir.'/system/modules');
        $fs->remove($this->rootDir.'/'.$this->webDir.'/vendor');

        $this->symlinkFiles($this->uploadPath);
        $this->symlinkModules();
        $this->symlinkThemes();

        // Symlink the assets and themes directory
        $this->symlink('assets', $this->webDir.'/assets');
        $this->symlink('system/themes', $this->webDir.'/system/themes');

        // Symlinks the logs directory
        $this->symlink($this->getRelativePath($this->logsDir), 'system/logs');

        $this->triggerSymlinkEvent();
    }

    private function symlinkFiles(string $uploadPath): void
    {
        $this->createSymlinksFromFinder(
            $this->findIn($this->rootDir.'/'.$uploadPath)->files()->depth('> 0')->name('.public'),
            $uploadPath
        );
    }

    private function symlinkModules(): void
    {
        $filter = function (SplFileInfo $file): bool {
            return HtaccessAnalyzer::create($file)->grantsAccess();
        };

        $this->createSymlinksFromFinder(
            $this->findIn($this->rootDir.'/system/modules')->files()->filter($filter)->name('.htaccess'),
            'system/modules'
        );
    }

    private function symlinkThemes(): void
    {
        /** @var SplFileInfo[] $themes */
        $themes = $this->resourceFinder->findIn('themes')->depth(0)->directories();

        foreach ($themes as $theme) {
            $path = $this->getRelativePath($theme->getPathname());

            if (0 === strncmp($path, 'system/modules/', 15)) {
                continue;
            }

            $this->symlink($path, 'system/themes/'.basename($path));
        }
    }

    private function createSymlinksFromFinder(Finder $finder, string $prepend): void
    {
        $files = $this->filterNestedPaths($finder, $prepend);

        foreach ($files as $file) {
            $path = rtrim($prepend.'/'.$file->getRelativePath(), '/');
            $this->symlink($path, $this->webDir.'/'.$path);
        }
    }

    private function triggerSymlinkEvent(): void
    {
        $event = new GenerateSymlinksEvent();

        $this->eventDispatcher->dispatch(ContaoCoreEvents::GENERATE_SYMLINKS, $event);

        foreach ($event->getSymlinks() as $target => $link) {
            $this->symlink($target, $link);
        }
    }

    /**
     * The method will try to generate relative symlinks and fall back to generating
     * absolute symlinks if relative symlinks are not supported (see #208).
     */
    private function symlink(string $target, string $link): void
    {
        $target = strtr($target, '\\', '/');
        $link = strtr($link, '\\', '/');

        try {
            SymlinkUtil::symlink($target, $link, $this->rootDir);

            $this->rows[] = [
                sprintf(
                    '<fg=green;options=bold>%s</>',
                    '\\' === \DIRECTORY_SEPARATOR ? 'OK' : "\xE2\x9C\x94" // HEAVY CHECK MARK (U+2714)
                ),
                $link,
                $target,
            ];
        } catch (\Exception $e) {
            $this->statusCode = 1;

            $this->rows[] = [
                sprintf(
                    '<fg=red;options=bold>%s</>',
                    '\\' === \DIRECTORY_SEPARATOR ? 'ERROR' : "\xE2\x9C\x98" // HEAVY BALLOT X (U+2718)
                ),
                $link,
                sprintf('<error>%s</error>', $e->getMessage()),
            ];
        }
    }

    /**
     * Returns a finder instance to find files in the given path.
     */
    private function findIn(string $path): Finder
    {
        return Finder::create()
            ->ignoreDotFiles(false)
            ->sort(
                function (SplFileInfo $a, SplFileInfo $b): int {
                    $countA = substr_count(strtr($a->getRelativePath(), '\\', '/'), '/');
                    $countB = substr_count(strtr($b->getRelativePath(), '\\', '/'), '/');

                    return $countA <=> $countB;
                }
            )
            ->followLinks()
            ->in($path)
        ;
    }

    /**
     * Filters nested paths so only the top folder is symlinked.
     *
     * @return SplFileInfo[]
     */
    private function filterNestedPaths(Finder $finder, string $prepend): array
    {
        $parents = [];
        $files = iterator_to_array($finder);

        foreach ($files as $key => $file) {
            $path = rtrim(strtr($prepend.'/'.$file->getRelativePath(), '\\', '/'), '/');

            if (!empty($parents)) {
                $parent = \dirname($path);

                while (false !== strpos($parent, '/')) {
                    if (\in_array($parent, $parents, true)) {
                        $this->rows[] = [
                            sprintf(
                                '<fg=yellow;options=bold>%s</>',
                                '\\' === \DIRECTORY_SEPARATOR ? 'WARNING' : '!'
                            ),
                            $this->webDir.'/'.$path,
                            sprintf('<comment>Skipped because %s will be symlinked.</comment>', $parent),
                        ];

                        unset($files[$key]);
                        break;
                    }

                    $parent = \dirname($parent);
                }
            }

            $parents[] = $path;
        }

        return array_values($files);
    }

    private function getRelativePath(string $path): string
    {
        return str_replace(strtr($this->rootDir, '\\', '/').'/', '', strtr($path, '\\', '/'));
    }
}
