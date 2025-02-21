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

use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Yaml\Yaml;

class LanguageFileGenerator implements GeneratorInterface
{
    public function __construct(
        private readonly FileManager $fileManager,
        private readonly string $projectDir,
    ) {
    }

    public function generate(array $options): string
    {
        $options = $this->getOptionsResolver()->resolve($options);
        $target = Path::join($this->projectDir, 'translations', \sprintf('%s.%s.yaml', $options['domain'], $options['language']));

        if ($this->fileManager->fileExists($target)) {
            $translations = Yaml::parse($this->fileManager->getFileContents($target));
        } else {
            $translations = [];
        }

        $translations = array_merge_recursive($translations, $options['variables']);

        $this->fileManager->dumpFile($target, Yaml::dump($translations, inline: 10));

        return Path::makeRelative($target, $this->projectDir);
    }

    private function getOptionsResolver(): OptionsResolver
    {
        $resolver = new OptionsResolver();
        $resolver->setRequired(['domain', 'language', 'variables']);
        $resolver->setAllowedTypes('variables', ['array']);

        return $resolver;
    }
}
