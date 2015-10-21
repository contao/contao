<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\DependencyInjection\Compiler;

use Contao\CoreBundle\DependencyInjection\Compiler\AddResourcesPathsPass;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests the AddResourcesPathsPass class.
 *
 * @author Leo Feyer <http://github.com/leofeyer>
 */
class AddResourcesPathsPassTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $pass = new AddResourcesPathsPass();

        $this->assertInstanceOf('Contao\\CoreBundle\\DependencyInjection\\Compiler\\AddResourcesPathsPass', $pass);
    }

    /**
     * Tests the getResourcesPath() method.
     */
    public function testGetResourcesPath()
    {
        $pass = new AddResourcesPathsPass();

        $container = new ContainerBuilder();
        $container->setParameter('kernel.root_dir', $this->getRootDir() . '/app');

        $container->setParameter('kernel.bundles', [
            'FrameworkBundle' => 'Symfony\\Bundle\\FrameworkBundle\\FrameworkBundle',
            'ContaoTestBundle' => 'Contao\\TestBundle\\ContaoTestBundle',
            'foobar' => 'Contao\\CoreBundle\\HttpKernel\\Bundle\\ContaoModuleBundle',
        ]);

        $pass->process($container);

        $this->assertTrue($container->hasParameter('contao.resources_paths'));

        $path = $this->getRootDir() . '/vendor/contao/test-bundle';

        if ('\\' === DIRECTORY_SEPARATOR) {
            $path = strtr($path, '/', '\\');
        }

        $this->assertEquals(
            [
                $path . '/Resources/contao',
                $this->getRootDir() . '/system/modules/foobar',
                $this->getRootDir() . '/app/Resources/contao',
            ],
            $container->getParameter('contao.resources_paths')
        );
    }
}
