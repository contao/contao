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

use Contao\MakerBundle\Config\XliffMerger;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LanguageFileGenerator implements GeneratorInterface
{
    private FileManager $fileManager;
    private XliffMerger $xliffMerger;
    private string $projectDir;

    public function __construct(FileManager $fileManager, XliffMerger $xliffMerger, string $projectDir)
    {
        $this->fileManager = $fileManager;
        $this->xliffMerger = $xliffMerger;
        $this->projectDir = $projectDir;
    }

    public function generate(array $options): string
    {
        $options = $this->getOptionsResolver()->resolve($options);

        $source = $this->getSourcePath($options['source']);
        $target = Path::join($this->projectDir, 'contao/languages', $options['language'], $options['domain'].'.xlf');
        $contents = $this->fileManager->parseTemplate($source, $options['variables']);
        $fileExists = $this->fileManager->fileExists($target);

        if ($fileExists) {
            $root = new \DOMDocument();
            $root->load($target);

            $document = new \DOMDocument();
            $document->loadXML($contents);

            $mergedDocument = $this->xliffMerger->merge($root, $document);
            $contents = (string) $mergedDocument->saveXML();
        }

        $this->fileManager->dumpFile($target, $contents);

        return Path::join('contao/languages', $options['language'], $options['domain'].'.xlf');
    }

    private function getOptionsResolver(): OptionsResolver
    {
        $resolver = new OptionsResolver();
        $resolver->setRequired(['domain', 'source', 'language', 'variables']);
        $resolver->setAllowedTypes('variables', ['array']);

        return $resolver;
    }

    private function getSourcePath(string $path): string
    {
        return Path::join(__DIR__, '../../skeleton', $path);
    }
}
