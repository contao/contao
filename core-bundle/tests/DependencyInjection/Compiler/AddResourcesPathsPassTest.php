<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\DependencyInjection\Compiler;

use Contao\CoreBundle\DependencyInjection\Compiler\AddResourcesPathsPass;
use Contao\CoreBundle\HttpKernel\Bundle\ContaoModuleBundle;
use Contao\CoreBundle\Tests\TestCase;
use Contao\TestBundle\ContaoTestBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AddResourcesPathsPassTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $pass = new AddResourcesPathsPass();

        $this->assertInstanceOf('Contao\CoreBundle\DependencyInjection\Compiler\AddResourcesPathsPass', $pass);
    }

    public function testAddsTheResourcesPaths(): void
    {
        $container = $this->mockContainer($this->getFixturesDir());

        $container->setParameter('kernel.bundles', [
            'FrameworkBundle' => FrameworkBundle::class,
            'ContaoTestBundle' => ContaoTestBundle::class,
            'foobar' => ContaoModuleBundle::class,
        ]);

        $pass = new AddResourcesPathsPass();
        $pass->process($container);

        $this->assertTrue($container->hasParameter('contao.resources_paths'));

        $path = $this->getFixturesDir().'/vendor/contao/test-bundle';

        if ('\\' === DIRECTORY_SEPARATOR) {
            $path = strtr($path, '/', '\\');
        }

        $this->assertSame(
            [
                $path.'/Resources/contao',
                $this->getFixturesDir().'/system/modules/foobar',
                $this->getFixturesDir().'/app/Resources/contao',
            ],
            $container->getParameter('contao.resources_paths')
        );
    }
}
