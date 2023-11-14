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

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;

#[AsCommand(
    name: 'skeleton:install',
    description: 'Installs the skeleton files of the manager bundle.',
    aliases: ['contao:install-web-dir'], // Backwards compatibility
)]
class InstallSkeletonCommand extends Command
{
    private Filesystem|null $fs = null;

    private SymfonyStyle|null $io = null;

    public function __construct(private readonly string $projectDir)
    {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this->addArgument('web-dir', InputArgument::OPTIONAL, 'The web directory (defaults to "public")');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->fs = new Filesystem();
        $this->io = new SymfonyStyle($input, $output);

        $webDir = $input->getArgument('web-dir') ?? 'public';

        $this->addConsole();
        $this->addHtaccess($webDir);
        $this->addFiles($webDir);
        $this->purgeOldFiles($webDir);

        return Command::SUCCESS;
    }

    /**
     * Adds the bin/console entry point.
     */
    private function addConsole(): void
    {
        $sourcePath = Path::canonicalize(__DIR__.'/../../skeleton/bin/console');
        $targetPath = Path::join($this->projectDir, 'bin/console');

        $this->fs->copy($sourcePath, $targetPath, true);
        $this->io->writeln('Added the <comment>bin/console</comment> file.');
    }

    /**
     * Adds the .htaccess file or merges it with an existing one.
     */
    private function addHtaccess(string $webDir): void
    {
        $sourcePath = Path::canonicalize(__DIR__.'/../../skeleton/public/.htaccess');
        $targetPath = Path::join($this->projectDir, $webDir, '.htaccess');

        if (!$this->fs->exists($targetPath)) {
            $this->fs->copy($sourcePath, $targetPath, true);
            $this->io->writeln("Added the <comment>$webDir/.htaccess</comment> file.");

            return;
        }

        $existingContent = file_get_contents($targetPath);

        // Return if there already is a rewrite rule
        if (preg_match('/^\s*RewriteRule\s/im', (string) $existingContent)) {
            return;
        }

        $this->fs->dumpFile($targetPath, $existingContent."\n\n".file_get_contents($sourcePath));
        $this->io->writeln("Updated the <comment>$webDir/.htaccess</comment> file.");
    }

    /**
     * Adds files from skeleton/public to the application's public directory.
     */
    private function addFiles(string $webDir): void
    {
        $finder = Finder::create()->files()->in(__DIR__.'/../../skeleton/public');

        foreach ($finder as $file) {
            $targetPath = Path::join($this->projectDir, $webDir, $file->getRelativePathname());

            $this->fs->copy($file->getPathname(), $targetPath, true);
            $this->io->writeln(sprintf('Added the <comment>%s/%s</comment> file.', $webDir, $file->getFilename()));
        }
    }

    /**
     * Purges old entry points.
     */
    private function purgeOldFiles(string $webDir): void
    {
        foreach (['app_dev.php', 'install.php'] as $file) {
            if ($this->fs->exists($path = Path::join($this->projectDir, $webDir, $file))) {
                $this->fs->remove($path);
                $this->io->writeln("Deleted the <comment>$webDir/$file</comment> file.");
            }
        }
    }
}
