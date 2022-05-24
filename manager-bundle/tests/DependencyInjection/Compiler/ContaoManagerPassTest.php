<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Tests\DependencyInjection\Compiler;

use Contao\ManagerBundle\DependencyInjection\Compiler\ContaoManagerPass;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;

class ContaoManagerPassTest extends ContaoTestCase
{
    public function testAddsTheManagerPathIfTheFileExists(): void
    {
        $fs = new Filesystem();
        $fs->dumpFile($this->getTempDir().'/contao-manager.phar.php', '');

        $container = new ContainerBuilder();
        $container->setParameter('contao.web_dir', $this->getTempDir());
        $container->setParameter('contao_manager.manager_path', null);

        $pass = new ContaoManagerPass();
        $pass->process($container);

        $this->assertSame('contao-manager.phar.php', $container->getParameter('contao_manager.manager_path'));

        $fs->remove($this->getTempDir().'/contao-manager.phar.php');
    }

    public function testDoesNotAddTheManagerPathIfTheFileDoesNotExist(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('contao.web_dir', $this->getTempDir());
        $container->setParameter('contao_manager.manager_path', null);

        $pass = new ContaoManagerPass();
        $pass->process($container);

        $this->assertNull($container->getParameter('contao_manager.manager_path'));
    }

    public function testAddsACustomManagerPathIfTheFileExists(): void
    {
        $fs = new Filesystem();
        $fs->dumpFile($this->getTempDir().'/custom.phar.php', '');

        $container = new ContainerBuilder();
        $container->setParameter('contao.web_dir', $this->getTempDir());
        $container->setParameter('contao_manager.manager_path', 'custom.phar.php');

        $pass = new ContaoManagerPass();
        $pass->process($container);

        $this->assertSame('custom.phar.php', $container->getParameter('contao_manager.manager_path'));

        $fs->remove($this->getTempDir().'/custom.phar.php');
    }

    public function testDoesNotAddACustomManagerPathIfTheFileDoesNotExist(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('contao.web_dir', $this->getTempDir());
        $container->setParameter('contao_manager.manager_path', 'custom.phar.php');

        $pass = new ContaoManagerPass();

        $this->expectException('LogicException');
        $this->expectExceptionMessageMatches('/^You have configured "contao_manager.manager_path" but the file/');

        $pass->process($container);
    }
}
