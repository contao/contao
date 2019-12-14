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
use Contao\NewBundle\ContaoNewBundle;
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
            'ContaoNewBundle' => ContaoNewBundle::class,
            'foobar' => ContaoModuleBundle::class,
        ];

        $meta = [
            'FrameworkBundle' => ['path' => (new FrameworkBundle())->getPath()],
            'ContaoTestBundle' => ['path' => (new ContaoTestBundle())->getPath()],
            'ContaoNewBundle' => ['path' => (new ContaoNewBundle())->getPath()],
            'foobar' => ['path' => $this->getFixturesDir().'/system/modules/foobar'],
        ];

        $container = $this->getContainerWithContaoConfiguration($this->getFixturesDir());
        $container->setParameter('kernel.bundles', $bundles);
        $container->setParameter('kernel.bundles_metadata', $meta);

        $pass = new AddResourcesPathsPass();
        $pass->process($container);

        $this->assertTrue($container->hasParameter('contao.resources_paths'));

        $testPath = $this->getFixturesDir().'/vendor/contao/test-bundle';
        $newPath = $this->getFixturesDir().'/vendor/contao/new-bundle';

        if ('\\' === \DIRECTORY_SEPARATOR) {
            $testPath = strtr($testPath, '/', '\\');
            $newPath = strtr($newPath, '/', '\\');
        }

        $this->assertSame(
            [
                $testPath.'/Resources/contao',
                $newPath.'/contao',
                $this->getFixturesDir().'/system/modules/foobar',
                $this->getFixturesDir().'/contao',
                $this->getFixturesDir().'/app/Resources/contao',
                $this->getFixturesDir().'/src/Resources/contao',
            ],
            $container->getParameter('contao.resources_paths')
        );
    }
}
