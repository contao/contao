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
    private ?Filesystem $fs = null;
    private ?SymfonyStyle $io = null;
    private string $projectDir;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('contao:install-web-dir')
            ->addArgument('target', InputArgument::OPTIONAL, 'The target directory')
            ->setDescription('Installs the files in the public directory')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->fs = new Filesystem();
        $this->io = new SymfonyStyle($input, $output);

        $webDir = $input->getArgument('target');

        if (null === $webDir) {
            if ($this->fs->exists($this->projectDir.'/web')) {
                $webDir = 'web'; // backwards compatibility
            } else {
                $webDir = 'public';
            }
        }

        $path = Path::join($this->projectDir, $webDir);

        $this->addHtaccess($path);
        $this->addFiles($path);
        $this->purgeOldFiles($path);

        return 0;
    }

    /**
     * Adds the .htaccess file or merges it with an existing one.
     */
    private function addHtaccess(string $webDir): void
    {
        $sourcePath = __DIR__.'/../Resources/skeleton/public/.htaccess';
        $targetPath = Path::join($webDir, '.htaccess');

        if (!$this->fs->exists($targetPath)) {
            $this->fs->copy($sourcePath, $targetPath, true);
            $this->io->writeln('Added the <comment>public/.htaccess</comment> file.');

            return;
        }

        $existingContent = file_get_contents($targetPath);

        // Return if there already is a rewrite rule
        if (preg_match('/^\s*RewriteRule\s/im', $existingContent)) {
            return;
        }

        $this->fs->dumpFile($targetPath, $existingContent."\n\n".file_get_contents($sourcePath));
        $this->io->writeln('Updated the <comment>public/.htaccess</comment> file.');
    }

    /**
     * Adds files from Resources/skeleton/public to the application's public directory.
     */
    private function addFiles(string $webDir): void
    {
        $finder = Finder::create()->files()->in(__DIR__.'/../Resources/skeleton/public');

        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            $this->fs->copy($file->getPathname(), Path::join($webDir, $file->getRelativePathname()), true);
            $this->io->writeln(sprintf('Added the <comment>public/%s</comment> file.', $file->getFilename()));
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
                $this->io->writeln("Deleted the <comment>public/$file</comment> file.");
            }
        }
    }
}
