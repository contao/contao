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
use Symfony\Component\Filesystem\Path;

class AddResourcesPathsPassTest extends TestCase
{
    public function testAddsTheResourcesPaths(): void
    {
        $fixturesDir = Path::normalize($this->getFixturesDir());

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
            'foobar' => ['path' => $fixturesDir.'/system/modules/foobar'],
        ];

        $container = $this->getContainerWithContaoConfiguration($fixturesDir);
        $container->setParameter('kernel.bundles', $bundles);
        $container->setParameter('kernel.bundles_metadata', $meta);

        $pass = new AddResourcesPathsPass();
        $pass->process($container);

        $this->assertTrue($container->hasParameter('contao.resources_paths'));

        $testPath = $fixturesDir.'/vendor/contao/test-bundle';
        $newPath = $fixturesDir.'/vendor/contao/new-bundle';

        $this->assertSame(
            [
                $testPath.'/Resources/contao',
                $newPath.'/contao',
                $fixturesDir.'/system/modules/foobar',
                $fixturesDir.'/contao',
            ],
            $container->getParameter('contao.resources_paths'),
        );
    }
}
