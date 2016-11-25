<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Imagine\Exception\RuntimeException;

/**
 * Sets the available Imagine class name in the container.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class AddImagineClassPass implements CompilerPassInterface
{
    /**
     * @var array
     */
    private $magicks = ['Imagick', 'Gmagick'];

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $container->getDefinition('contao.image.imagine')->setClass($this->getImagineImplementation());
    }

    /**
     * Returns the available Imagine implementation.
     *
     * @return string
     */
    private function getImagineImplementation()
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

        return 'Imagine\Gd\Imagine'; // see #616
    }
}
