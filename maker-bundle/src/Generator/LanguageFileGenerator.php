<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\MakerBundle\Generator;

use Contao\MakerBundle\Filesystem\ContaoDirectoryLocator;
use Contao\MakerBundle\Translation\XliffMerger;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Webmozart\PathUtil\Path;

class LanguageFileGenerator implements GeneratorInterface
{
    private FileManager $fileManager;
    private Filesystem $filesystem;
    private XliffMerger $xliffMerger;
    private ContaoDirectoryLocator $directoryLocator;

    public function __construct(FileManager $fileManager, Filesystem $filesystem, XliffMerger $xliffMerger, ContaoDirectoryLocator $directoryLocator)
    {
        $this->fileManager = $fileManager;
        $this->filesystem = $filesystem;
        $this->xliffMerger = $xliffMerger;
        $this->directoryLocator = $directoryLocator;
    }

    public function generate(array $options): string
    {
        $options = $this->getOptionsResolver()->resolve($options);

        $source = $this->getSourcePath($options['source']);
        $target = Path::join($this->directoryLocator->getConfigDirectory(), 'languages', $options['language'], $options['domain'].'.xlf');
        $contents = $this->fileManager->parseTemplate($source, $options['variables']);
        $fileExists = $this->filesystem->exists($target);

        if ($fileExists) {
            $root = new \DOMDocument();
            $root->load($target);

            $document = new \DOMDocument();
            $document->loadXML($contents);

            $mergedDocument = $this->xliffMerger->merge($root, $document);
            $contents = (string) $mergedDocument->saveXML();
        }

        $this->filesystem->dumpFile($target, $contents);

        $comment = !$fileExists ? '<fg=blue>created</>' : '<fg=yellow>updated</>';
        $this->addCommentLine($options['io'], $comment, $target);

        return $target;
    }

    private function getOptionsResolver(): OptionsResolver
    {
        $resolver = new OptionsResolver();
        $resolver->setRequired(['domain', 'source', 'language', 'variables', 'io']);
        $resolver->setAllowedTypes('io', [ConsoleStyle::class]);
        $resolver->setAllowedTypes('variables', ['array']);

        return $resolver;
    }

    private function addCommentLine(ConsoleStyle $io, string $action, string $target): void
    {
        $io->comment(sprintf(
            '%s: %s',
            $action,
            $this->fileManager->relativizePath($target)
        ));
    }

    private function getSourcePath(string $path): string
    {
        return Path::join(__DIR__, '../Resources/skeleton', $path);
    }
}
