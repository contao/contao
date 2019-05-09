<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\DependencyInjection\Compiler;

use Contao\CoreBundle\DependencyInjection\Compiler\AddResourcesPathsPass;
use Contao\CoreBundle\HttpKernel\Bundle\ContaoModuleBundle;
use Contao\CoreBundle\Tests\TestCase;
use Contao\TestBundle\ContaoTestBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;

class AddResourcesPathsPassTest extends TestCase
{
    /**
     * @group legacy
     *
     * @expectedDeprecation Using "app/Resources/contao" has been deprecated %s.
     * @expectedDeprecation Using "src/Resources/contao" has been deprecated %s.
     */
    public function testAddsTheResourcesPaths(): void
    {
        $bundles = [
            'FrameworkBundle' => FrameworkBundle::class,
            'ContaoTestBundle' => ContaoTestBundle::class,
            'foobar' => ContaoModuleBundle::class,
        ];

        $container = $this->mockContainer($this->getFixturesDir());
        $container->setParameter('kernel.bundles', $bundles);

        $pass = new AddResourcesPathsPass();
        $pass->process($container);

        $this->assertTrue($container->hasParameter('contao.resources_paths'));

        $path = $this->getFixturesDir().'/vendor/contao/test-bundle';

        if ('\\' === \DIRECTORY_SEPARATOR) {
            $path = strtr($path, '/', '\\');
        }

        $this->assertSame(
            [
                $path.'/Resources/contao',
                $this->getFixturesDir().'/system/modules/foobar',
                $this->getFixturesDir().'/contao',
                $this->getFixturesDir().'/app/Resources/contao',
                $this->getFixturesDir().'/src/Resources/contao',
            ],
            $container->getParameter('contao.resources_paths')
        );
    }
}
