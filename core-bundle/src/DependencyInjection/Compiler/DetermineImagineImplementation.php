<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
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
class DetermineImagineImplementation implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $container->setParameter('contao.image.imagine.class', $this->getImagineImplementation());
    }

    /**
     * Returns the available Imagine implementation, one of Imagick, Gmagick or Gd
     *
     * @return string The class name of the available Imagine implementation
     */
    private function getImagineImplementation()
    {
        foreach (['Imagick', 'Gmagick', 'Gd'] as $name) {

            $class = 'Imagine\\' . $name . '\\Imagine';

            try {
                new $class();
                break;
            } catch (RuntimeException $exception) {
                $class = null;
            }

        }

        if (null === $class) {
            throw new \RuntimeException('No Imagine implementation is available (IMagick, GMagick or GD)');
        }

        return $class;
    }
}
