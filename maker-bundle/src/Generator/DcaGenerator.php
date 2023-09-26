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

class DcaGenerator implements GeneratorInterface
{
    private FileManager $fileManager;
    private string $projectDir;

    public function __construct(FileManager $fileManager, string $projectDir)
    {
        $this->fileManager = $fileManager;
        $this->projectDir = $projectDir;
    }

    public function generate(array $options): string
    {
        $options = $this->getOptionsResolver()->resolve($options);

        $source = $this->getSourcePath($options['source']);
        $target = Path::join($this->projectDir, 'contao/dca', $options['domain'].'.php');
        $fileExists = $this->fileManager->fileExists($target);

        $variables = array_merge(
            [
                'append' => $fileExists,
                'element_name' => $options['element'],
            ],
            $options['variables']
        );

        $contents = $this->fileManager->parseTemplate($source, $variables);
        $contents = ltrim($contents);

        if ($fileExists) {
            $contents = file_get_contents($target)."\n".rtrim($contents)."\n";
        }

        $this->fileManager->dumpFile($target, $contents);

        return Path::join('contao/dca', $options['domain'].'.php');
    }

    private function getOptionsResolver(): OptionsResolver
    {
        $resolver = new OptionsResolver();
        $resolver->setRequired(['domain', 'source', 'element']);
        $resolver->setDefaults(['variables' => []]);

        return $resolver;
    }

    private function getSourcePath(string $path): string
    {
        return Path::join(__DIR__, '../../skeleton', $path);
    }
}
