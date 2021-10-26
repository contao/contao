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
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClassGenerator implements GeneratorInterface
{
    private Generator $generator;

    public function __construct(Generator $generator)
    {
        $this->generator = $generator;
    }

    public function generate(array $options): string
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $options = $resolver->resolve($options);

        return $this->generator->generateClass(
            $options['fqcn'],
            $this->getSourcePath($options['source']),
            $options['variables']
        );
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired([
            'fqcn',
            'source',
        ]);

        $resolver->setDefaults([
            'variables' => [],
        ]);
    }

    private function getSourcePath(string $path): string
    {
        return sprintf('%s/../Resources/skeleton/%s', __DIR__, ltrim($path, '/'));
    }
}
