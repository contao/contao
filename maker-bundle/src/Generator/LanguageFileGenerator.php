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
use Symfony\Component\Yaml\Yaml;

class LanguageFileGenerator implements GeneratorInterface
{
    public function __construct(
        private readonly FileManager $fileManager,
        private readonly XliffMerger $xliffMerger,
        private readonly string $projectDir,
    ) {
    }

    public function generate(array $options): string
    {
        $options = $this->getOptionsResolver()->resolve($options);

        $source = $this->getSourcePath($options['source']);

        if ('yaml' !== pathinfo($source, PATHINFO_EXTENSION)) {
            throw new \RuntimeException('Source file needs to be in YAML format.');
        }

        $target = Path::join($this->projectDir, 'translations', \sprintf('%s.%s.yaml', $options['domain'], $options['language']));
        $variables = $options['variables'];
        $contents = $this->fileManager->parseTemplate($source, $options['variables']);
        $yaml = Yaml::parse($contents);

        if ($this->fileManager->fileExists($target)) {
            $yaml['CTE'][$variables['element']] = [
                $variables['name'],
                $variables['description'],
            ];
        }

        $this->fileManager->dumpFile($target, Yaml::dump($yaml, inline: 3));

        return Path::makeRelative($target, $this->projectDir);
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
        return Path::join(__DIR__.'/../../skeleton', $path);
    }
}
