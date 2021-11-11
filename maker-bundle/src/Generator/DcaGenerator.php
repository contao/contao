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

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Webmozart\PathUtil\Path;

class DcaGenerator implements GeneratorInterface
{
    private Filesystem $filesystem;
    private FileManager $fileManager;
    private string $projectDir;

    public function __construct(Filesystem $filesystem, FileManager $fileManager, string $projectDir)
    {
        $this->filesystem = $filesystem;
        $this->fileManager = $fileManager;
        $this->projectDir = $projectDir;
    }

    public function generate(array $options): string
    {
        $options = $this->getOptionsResolver()->resolve($options);

        $source = $this->getSourcePath($options['source']);
        $target = Path::join($this->projectDir, 'contao/dca', $options['domain'].'.php');
        $fileExists = $this->filesystem->exists($target);

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
            $contents = file_get_contents($target)."\n".rtrim($contents);
        }

        $this->filesystem->dumpFile($target, $contents);

        $comment = !$fileExists ? '<fg=blue>created</>' : '<fg=yellow>updated</>';
        $this->addCommentLine($options['io'], $comment, $target);

        return $target;
    }

    private function getOptionsResolver(): OptionsResolver
    {
        $resolver = new OptionsResolver();
        $resolver->setRequired(['domain', 'source', 'element', 'io']);
        $resolver->setDefaults(['variables' => []]);
        $resolver->setAllowedTypes('io', [ConsoleStyle::class]);

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
