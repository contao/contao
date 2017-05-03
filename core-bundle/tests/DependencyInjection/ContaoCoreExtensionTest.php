<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\DependencyInjection;

use Contao\CoreBundle\DependencyInjection\ContaoCoreExtension;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * Tests the ContaoCoreExtension class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ContaoCoreExtensionTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $extension = new ContaoCoreExtension();

        $this->assertInstanceOf('Contao\CoreBundle\DependencyInjection\ContaoCoreExtension', $extension);
    }

    /**
     * Tests the getAlias() method.
     */
    public function testGetAlias()
    {
        $extension = new ContaoCoreExtension();

        $this->assertEquals('contao', $extension->getAlias());
    }

    /**
     * Tests adding the bundle services to the container.
     */
    public function testLoad()
    {
        $container = new ContainerBuilder(
            new ParameterBag([
                'kernel.debug' => false,
                'kernel.project_dir' => $this->getRootDir(),
                'kernel.root_dir' => $this->getRootDir().'/app',
            ])
        );

        $params = [
            'contao' => [
                'encryption_key' => 'foobar',
                'localconfig' => ['foo' => 'bar'],
            ],
        ];

        $extension = new ContaoCoreExtension();
        $extension->load($params, $container);

        $this->assertTrue($container->has('contao.listener.add_to_search_index'));
    }

    /**
     * Tests the deprecated contao.image.target_path configuration option.
     *
     * @expectedDeprecation Using the contao.image.target_path parameter has been deprecated %s.
     * @group legacy
     */
    public function testImageTargetPath()
    {
        $container = new ContainerBuilder(
            new ParameterBag([
                'kernel.debug' => false,
                'kernel.project_dir' => $this->getRootDir(),
                'kernel.root_dir' => $this->getRootDir().'/app',
            ])
        );

        $extension = new ContaoCoreExtension();
        $extension->load([], $container);

        $this->assertEquals(
            str_replace('/', DIRECTORY_SEPARATOR, $this->getRootDir().'/assets/images'),
            $container->getParameter('contao.image.target_dir')
        );

        $params = [
            'contao' => [
                'image' => ['target_path' => 'my/custom/dir'],
            ],
        ];

        $extension = new ContaoCoreExtension();
        $extension->load($params, $container);

        $this->assertEquals(
            str_replace('/', DIRECTORY_SEPARATOR, $this->getRootDir()).'/my/custom/dir',
            $container->getParameter('contao.image.target_dir')
        );
    }
}
