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

use Contao\CoreBundle\DependencyInjection\Compiler\ConfigureFilesystemPass;
use Contao\CoreBundle\DependencyInjection\Filesystem\FilesystemConfiguration;
use Contao\CoreBundle\Tests\Fixtures\Filesystem\FilesystemConfiguringExtension;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

class ConfigureFilesystemPassTest extends TestCase
{
    public function testCallsExtensionsToConfigureTheFilesystem(): void
    {
        $container = $this->createMock(ContainerBuilder::class);

        $configureFilesystemExtensions = [];

        for ($i = 0; $i < 2; ++$i) {
            $extension = $this->getMockBuilder(FilesystemConfiguringExtension::class)->getMock();
            $extension
                ->expects($this->once())
                ->method('configureFilesystem')
                ->with($this->callback(
                    function (FilesystemConfiguration $config) use ($container): bool {
                        $this->assertSame($container, $config->getContainer());

                        return true;
                    }
                ))
            ;

            $configureFilesystemExtensions[] = $extension;
        }

        $regularExtension = $this->createMock(ExtensionInterface::class);

        $container
            ->method('getExtensions')
            ->willReturn([
                'foo' => $configureFilesystemExtensions[0],
                'bar' => $regularExtension,
                'baz' => $configureFilesystemExtensions[1],
            ])
        ;

        (new ConfigureFilesystemPass())->process($container);
    }
}
