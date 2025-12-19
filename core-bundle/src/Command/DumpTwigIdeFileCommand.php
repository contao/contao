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

use Contao\CoreBundle\Twig\Ide\NamespaceLookupFileGenerator;
use Contao\CoreBundle\Twig\Ide\NamespaceLookupFileWarmer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Path;

#[AsCommand(
    name: 'contao:dump-twig-ide-file',
    description: 'Dumps an "ide-twig.json" namespace lookup file that allows IDEs to understand the Contao template hierarchy.',
)]
class DumpTwigIdeFileCommand extends Command
{
    public function __construct(
        private readonly NamespaceLookupFileGenerator $namespaceLookupFileGenerator,
        private readonly string $buildDir,
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $defaultDir = Path::makeRelative(
            Path::join($this->buildDir, NamespaceLookupFileWarmer::CONTAO_IDE_DIR),
            $this->projectDir,
        );

        $this
            ->addArgument('dir', InputArgument::OPTIONAL, 'Target path relative to the project directory.', $defaultDir)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $targetDir = Path::canonicalize($input->getArgument('dir'));
        $io = new SymfonyStyle($input, $output);

        try {
            $this->namespaceLookupFileGenerator->write(Path::makeAbsolute($targetDir, $this->projectDir));
        } catch (IOException) {
            $io->error(\sprintf('Unable to write the "%s" namespace lookup file to "%s".', $targetDir, NamespaceLookupFileGenerator::FILE_NAME));

            return Command::FAILURE;
        }

        $io->success(\sprintf('The namespace lookup file was written to "%s/%s". Make sure the file is not ignored by your IDE.', $targetDir, NamespaceLookupFileGenerator::FILE_NAME));

        if ($this->getDefinition()->getArgument('dir')->getDefault() !== $input->getArgument('dir')) {
            $io->info('Re-run this command after installing extensions or introducing new @Contao namespace locations.');
        }

        return Command::SUCCESS;
    }
}
