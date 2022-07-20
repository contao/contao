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

use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TemplateGenerator implements GeneratorInterface
{
    public function __construct(private Generator $generator)
    {
    }

    public function generate(array $options): string
    {
        $options = $this->getOptionsResolver()->resolve($options);

        $this->generator->generateFile(
            $options['target'],
            $this->getSourcePath($options['source']),
            $options['variables']
        );

        return $options['target'];
    }

    private function getOptionsResolver(): OptionsResolver
    {
        $resolver = new OptionsResolver();
        $resolver->setRequired(['target', 'source']);
        $resolver->setDefaults(['variables' => []]);

        return $resolver;
    }

    private function getSourcePath(string $path): string
    {
        return Path::join(__DIR__.'/../../skeleton', $path);
    }
}
