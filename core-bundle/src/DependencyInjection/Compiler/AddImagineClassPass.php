<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\DependencyInjection\Compiler;

use Imagine\Exception\RuntimeException;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\ImagineInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

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
    private $magicks = ['Gmagick', 'Imagick'];

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $container->getDefinition('contao.image.imagine')->setClass($this->getImagineImplementation());
        $this->verifyValidFileExtensions($container->getParameter('contao.image.valid_extensions'));
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

        return Imagine::class; // see #616
    }

    private function verifyValidFileExtensions(array $extensions)
    {
        $imagineClass = $this->getImagineImplementation();

        /** @var ImagineInterface $imagine */
        $imagine = new $imagineClass();

        $extensions = array_map('strtolower', $extensions);
        $unsupportedExtensions = [];

        foreach ($extensions as $extension) {
            if (\in_array($extension, ['svg', 'svgz'], true)) {
                continue;
            }

            // Try to create an image with the specified format
            try {
                $imagine->create(new Box(1, 1))->get($extension);
            } catch (\Exception $e) {
                $unsupportedExtensions[] = $extension;
            } catch (\Throwable $e) {
                $unsupportedExtensions[] = $extension;
            }
        }

        if (\count($unsupportedExtensions) > 0) {
            trigger_error(sprintf('The image types %s from contao.image.valid_extensions are not supported in %s on this environment.', implode(',', $unsupportedExtensions), $imagineClass), E_USER_WARNING);
        }
    }
}
