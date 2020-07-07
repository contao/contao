<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Webmozart\PathUtil\Path;

/**
 * @internal
 */
class InstallWebDirCommand extends Command
{
    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * @var string
     */
    private $rootDir;

    public function __construct(string $rootDir)
    {
        $this->rootDir = $rootDir;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('contao:install-web-dir')
            ->addArgument('target', InputArgument::OPTIONAL, 'The target directory', 'web')
            ->setDescription('Installs the files in the "web" directory')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->fs = new Filesystem();
        $this->io = new SymfonyStyle($input, $output);

        $webDir = Path::join($this->rootDir, $input->getArgument('target'));

        $this->addHtaccess($webDir);
        $this->addFiles($webDir);
        $this->purgeOldFiles($webDir);

        return 0;
    }

    /**
     * Adds the .htaccess file or merges it with an existing one.
     */
    private function addHtaccess(string $webDir): void
    {
        $sourcePath = __DIR__.'/../Resources/skeleton/web/.htaccess';
        $targetPath = Path::join($webDir, '.htaccess');

        if (!$this->fs->exists($targetPath)) {
            $this->fs->copy($sourcePath, $targetPath, true);
            $this->io->writeln('Added the <comment>web/.htaccess</comment> file.');

            return;
        }

        $existingContent = file_get_contents($targetPath);

        // Return if there already is a rewrite rule
        if (preg_match('/^\s*RewriteRule\s/im', $existingContent)) {
            return;
        }

        $this->fs->dumpFile($targetPath, $existingContent."\n\n".file_get_contents($sourcePath));
        $this->io->writeln('Updated the <comment>web/.htaccess</comment> file.');
    }

    /**
     * Adds files from Resources/skeleton/web to the application's web directory.
     */
    private function addFiles(string $webDir): void
    {
        $finder = Finder::create()->files()->in(__DIR__.'/../Resources/skeleton/web');

        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            $this->fs->copy($file->getPathname(), Path::join($webDir, $file->getRelativePathname()), true);
            $this->io->writeln(sprintf('Added the <comment>web/%s</comment> file.', $file->getFilename()));
        }
    }

    /**
     * Purges old entry points.
     */
    private function purgeOldFiles(string $webDir): void
    {
        foreach (['app_dev.php', 'install.php'] as $file) {
            if ($this->fs->exists($path = Path::join($webDir, $file))) {
                $this->fs->remove($path);
                $this->io->writeln("Deleted the <comment>web/$file</comment> file.");
            }
        }
    }
}
