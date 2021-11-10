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
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Webmozart\PathUtil\Path;

class DcaGenerator implements GeneratorInterface
{
    private Filesystem $filesystem;
    private FileManager $fileManager;
    private ContaoDirectoryLocator $directoryLocator;

    public function __construct(Filesystem $filesystem, FileManager $fileManager, ContaoDirectoryLocator $directoryLocator)
    {
        $this->filesystem = $filesystem;
        $this->fileManager = $fileManager;
        $this->directoryLocator = $directoryLocator;
    }

    public function generate(array $options): string
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $options = $resolver->resolve($options);

        $source = $this->getSourcePath($options['source']);
        $target = Path::join($this->directoryLocator->getConfigDirectory(), 'dca', $options['domain'].'.php');

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
            /** @var string $targetContent */
            $targetContent = file_get_contents($target);
            $targetContent = rtrim($targetContent);

            $contents = $targetContent."\n\n".$contents;
        }

        $this->filesystem->dumpFile($target, $contents);

        $comment = !$fileExists ? '<fg=blue>created</>' : '<fg=yellow>updated</>';
        $this->addCommentLine($options['io'], $comment, $target);

        return $target;
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired([
            'domain',
            'source',
            'element',
            'io',
        ]);

        $resolver->setDefaults([
            'variables' => [],
        ]);

        $resolver->setAllowedTypes('io', [ConsoleStyle::class]);
    }

    protected function addCommentLine(ConsoleStyle $io, string $action, string $target): void
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
