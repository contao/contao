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

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('contao:install-web-dir')
            ->setDefinition([
                new InputArgument('target', InputArgument::OPTIONAL, 'The target directory', 'web'),
            ])
            ->setDescription('Installs the files in the "web" directory')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->fs = new Filesystem();
        $this->io = new SymfonyStyle($input, $output);

        $webDir = $this->rootDir.'/'.rtrim($input->getArgument('target'), '/');

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
        $htaccess = __DIR__.'/../Resources/skeleton/web/.htaccess';

        if (!file_exists($webDir.'/.htaccess')) {
            $this->fs->copy($htaccess, $webDir.'/.htaccess', true);
            $this->io->writeln('Added the <comment>web/.htaccess</comment> file.');

            return;
        }

        $existingContent = file_get_contents($webDir.'/.htaccess');

        // Return if there already is a rewrite rule
        if (preg_match('/^\s*RewriteRule\s/im', $existingContent)) {
            return;
        }

        $this->fs->dumpFile($webDir.'/.htaccess', $existingContent."\n\n".file_get_contents($htaccess));
        $this->io->writeln('Updated the <comment>web/.htaccess</comment> file.');
    }

    /**
     * Adds files from Resources/skeleton/web to the application's web directory.
     */
    private function addFiles(string $webDir): void
    {
        /** @var SplFileInfo[] $finder */
        $finder = Finder::create()->files()->in(__DIR__.'/../Resources/skeleton/web');

        foreach ($finder as $file) {
            if ($this->isExistingOptionalFile($file, $webDir)) {
                continue;
            }

            $this->fs->copy($file->getPathname(), $webDir.'/'.$file->getRelativePathname(), true);
            $this->io->writeln(sprintf('Added the <comment>web/%s</comment> file.', $file->getFilename()));
        }
    }

    /**
     * Purges old entry points.
     */
    private function purgeOldFiles(string $webDir): void
    {
        if (file_exists($webDir.'/app_dev.php')) {
            $this->fs->remove($webDir.'/app_dev.php');
            $this->io->writeln('Deleted the <comment>web/app_dev.php</comment> file.');
        }

        if (file_exists($webDir.'/install.php')) {
            $this->fs->remove($webDir.'/install.php');
            $this->io->writeln('Deleted the <comment>web/install.php</comment> file.');
        }
    }

    /**
     * Checks if an optional file exists.
     */
    private function isExistingOptionalFile(SplFileInfo $file, string $webDir): bool
    {
        $path = $file->getRelativePathname();

        return 'robots.txt' === $path && $this->fs->exists($webDir.'/'.$path);
    }
}
