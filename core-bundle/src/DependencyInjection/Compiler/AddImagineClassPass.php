<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\DependencyInjection\Compiler;

use Imagine\Exception\RuntimeException;
use Imagine\Gd\Imagine;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Sets the available Imagine class name in the container.
 */
class AddImagineClassPass implements CompilerPassInterface
{
    /**
     * @var array
     */
    private $magicks = ['Gmagick', 'Imagick'];

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $container->getDefinition('contao.image.imagine')->setClass($this->getImagineImplementation());
    }

    /**
     * Returns the available Imagine implementation.
     *
     * @return string
     */
    private function getImagineImplementation(): string
    {
        foreach ($this->magicks as $name) {
            $class = 'Imagine\\'.$name.'\Imagine';

            // Will throw an exception if the PHP implementation is not available
            try {
                new $class();
            } catch (RuntimeException $e) {
                continue;
            }

            return $class;
        }

        return Imagine::class; // see #616
    }
}
