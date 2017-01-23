<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\DependencyInjection\Compiler;

use Contao\CoreBundle\DependencyInjection\Compiler\AddImagineClassPass;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Tests the AddImagineClassPass class.
 *
 * @author Leo Feyer <http://github.com/leofeyer>
 */
class AddImagineClassPassTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $pass = new AddImagineClassPass();

        $this->assertInstanceOf('Contao\CoreBundle\DependencyInjection\Compiler\AddImagineClassPass', $pass);
    }

    /**
     * Tests processing the pass.
     */
    public function testProcess()
    {
        $container = new ContainerBuilder();
        $container->setDefinition('contao.image.imagine', new Definition());

        $pass = new AddImagineClassPass();
        $pass->process($container);

        $this->assertContains(
            $container->getDefinition('contao.image.imagine')->getClass(),
            [
                'Imagine\Gd\Imagine',
                'Imagine\Gmagick\Imagine',
                'Imagine\Imagick\Imagine',
            ]
        );
    }
}
