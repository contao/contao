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
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

#[AsCommand(
    name: 'contao:symlinks',
    description: 'Symlinks the public resources into the public directory.'
)]
class SymlinksCommand extends Command
{
    private array $rows = [];
    private string|null $webDir = null;
    private int $statusCode = Command::SUCCESS;

    public function __construct(
        private readonly string $projectDir,
        private readonly string $uploadPath,
        private readonly string $logsDir,
        private readonly ResourceFinderInterface $resourceFinder,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('target', InputArgument::OPTIONAL, 'The target directory');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->webDir = $input->getArgument('target') ?? 'public';

        $this->generateSymlinks();

        if (!empty($this->rows)) {
            $io = new SymfonyStyle($input, $output);
            $io->newLine();
            $io->table(['', 'Symlink', 'Target / Error'], $this->rows);
        }

        return $this->statusCode;
    }

    /**
     * Generates the symlinks in the web directory.
     */
    private function generateSymlinks(): void
    {
        $fs = new Filesystem();

        // Remove the base folders in the document root
        $fs->remove(Path::join($this->projectDir, $this->webDir, $this->uploadPath));
        $fs->remove(Path::join($this->projectDir, $this->webDir, 'system/modules'));
        $fs->remove(Path::join($this->projectDir, $this->webDir, 'vendor'));

        $this->symlinkFiles($this->uploadPath);
        $this->symlinkModules();
        $this->symlinkThemes();

        // Symlink the assets and themes directory
        $this->symlink('assets', Path::join($this->webDir, 'assets'));
        $this->symlink('system/themes', Path::join($this->webDir, 'system/themes'));

        // Symlinks the logs directory
        $this->symlink($this->getRelativePath($this->logsDir), 'system/logs');

        // Symlink the highlight.php styles
        if ($fs->exists(Path::join($this->projectDir, 'vendor/scrivo/highlight.php/styles'))) {
            $this->symlink(
                'vendor/scrivo/highlight.php/styles',
                Path::join($this->webDir, 'vendor/scrivo/highlight_php/styles')
            );
        }

        $this->triggerSymlinkEvent();
    }

    private function symlinkFiles(string $uploadPath): void
    {
        $this->createSymlinksFromFinder(
            $this->findIn(Path::join($this->projectDir, $uploadPath))->files()->depth('> 0')->name('.public'),
            $uploadPath
        );
    }

    private function symlinkModules(): void
    {
        $filter = static fn (SplFileInfo $file): bool => HtaccessAnalyzer::create($file)->grantsAccess();

        $this->createSymlinksFromFinder(
            $this->findIn(Path::join($this->projectDir, 'system/modules'))->files()->filter($filter)->name('.htaccess'),
            'system/modules'
        );
    }

    private function symlinkThemes(): void
    {
        $themes = $this->resourceFinder->findIn('themes')->depth(0)->directories();

        foreach ($themes as $theme) {
            $path = $this->getRelativePath($theme->getPathname());

            if (Path::isBasePath('system/modules', $path)) {
                continue;
            }

            $this->symlink($path, Path::join('system/themes', basename($path)));
        }
    }

    private function createSymlinksFromFinder(Finder $finder, string $prepend): void
    {
        $files = $this->filterNestedPaths($finder, $prepend);

        foreach ($files as $file) {
            $path = Path::join($prepend, $file->getRelativePath());
            $this->symlink($path, Path::join($this->webDir, $path));
        }
    }

    private function triggerSymlinkEvent(): void
    {
        $event = new GenerateSymlinksEvent();

        $this->eventDispatcher->dispatch($event, ContaoCoreEvents::GENERATE_SYMLINKS);

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
        try {
            SymlinkUtil::symlink($target, $link, $this->projectDir);

            $this->rows[] = [
                sprintf(
                    '<fg=green;options=bold>%s</>',
                    '\\' === \DIRECTORY_SEPARATOR ? 'OK' : "\xE2\x9C\x94" // HEAVY CHECK MARK (U+2714)
                ),
                $link,
                $target,
            ];
        } catch (\Exception $e) {
            $this->statusCode = Command::FAILURE;

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
                static function (SplFileInfo $a, SplFileInfo $b): int {
                    $countA = substr_count(Path::normalize($a->getRelativePath()), '/');
                    $countB = substr_count(Path::normalize($b->getRelativePath()), '/');

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
     * @return array<SplFileInfo>
     */
    private function filterNestedPaths(Finder $finder, string $prepend): array
    {
        /** @var array<string, SplFileInfo> $files */
        $files = iterator_to_array($finder);

        foreach ($files as $key => $file) {
            $path = $file->getRelativePath();

            foreach ($files as $otherFile) {
                $otherPath = $otherFile->getRelativePath();

                if ($path === $otherPath || !Path::isBasePath($otherPath, $path)) {
                    continue;
                }

                unset($files[$key]);

                $this->rows[] = [
                    sprintf('<fg=yellow;options=bold>%s</>', '\\' === \DIRECTORY_SEPARATOR ? 'WARNING' : '!'),
                    Path::join($this->webDir, $prepend, $path),
                    sprintf('<comment>Skipped because %s will be symlinked.</comment>', Path::join($prepend, $otherPath)),
                ];
            }
        }

        return array_values($files);
    }

    private function getRelativePath(string $path): string
    {
        return Path::makeRelative($path, $this->projectDir);
    }
}
