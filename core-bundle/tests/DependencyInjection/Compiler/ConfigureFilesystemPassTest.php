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
use Contao\CoreBundle\Filesystem\MountManager;
use Contao\CoreBundle\Tests\Fixtures\Filesystem\FilesystemConfiguringExtension;
use Contao\CoreBundle\Tests\TestCase;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Process\Process;

class ConfigureFilesystemPassTest extends TestCase
{
    /**
     * @var string|false
     */
    private $cwdBackup = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cwdBackup = getcwd();
    }

    protected function tearDown(): void
    {
        chdir($this->cwdBackup);

        parent::tearDown();
    }

    public function testCallsExtensionsToConfigureTheFilesystem(): void
    {
        $container = $this->createMock(ContainerBuilder::class);
        $container
            ->method('getParameterBag')
            ->willReturn(new ParameterBag([
                'kernel.project_dir' => $this->getTempDir(),
                'contao.upload_path' => '',
            ]))
        ;

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

    /**
     * @dataProvider provideSymlinks
     */
    public function testCreatesMountsForSymlinks(string $target, string $link): void
    {
        $tempDir = $this->getTempDir();
        $target = str_replace('<root>', $tempDir, $target);

        // Setup directories with symlink
        $filesystem = new Filesystem();
        $filesystem->mkdir(Path::join($tempDir, 'files'));
        $filesystem->dumpFile($dummyFile = Path::join($tempDir, 'vendor/foo/dummy.txt'), 'dummy');

        $this->createSymlink($target, $link, Path::join($tempDir, 'files'));
        $this->createSymlink($dummyFile, 'dummy.txt', Path::join($tempDir, 'files')); // should get ignored

        $container = new ContainerBuilder(
            new ParameterBag([
                'kernel.project_dir' => $tempDir,
                'contao.upload_path' => 'files',
            ])
        );

        $container
            ->setDefinition(
                $mountManagerId = 'contao.filesystem.mount_manager',
                $mountManagerDefinition = new Definition(MountManager::class)
            )
            ->setPublic(true)
        ;

        (new ConfigureFilesystemPass())->process($container);

        $methodCalls = $mountManagerDefinition->getMethodCalls();

        $this->assertCount(1, $methodCalls);
        $this->assertSame('mount', $methodCalls[0][0]);

        [$reference, $mountPath] = $methodCalls[0][1];

        $this->assertInstanceOf(Reference::class, $reference);
        $this->assertSame('files/foo', Path::normalize($mountPath));

        $adapter = $container->get((string) $reference);

        $this->assertInstanceOf(LocalFilesystemAdapter::class, $adapter);
        $this->assertSame('dummy', $adapter->read('dummy.txt'));

        $container->compile();

        $mountManager = $container->get($mountManagerId);

        $this->assertSame('dummy', $mountManager->read('files/foo/dummy.txt'));

        // Cleanup
        $filesystem->remove(Path::join($tempDir, 'files'));
        $filesystem->remove(Path::join($tempDir, 'vendor'));
    }

    public function provideSymlinks(): \Generator
    {
        yield 'absolute symlink' => [
            '<root>/vendor/foo',
            'foo',
        ];

        yield 'symlink relative to the files directory' => [
            '../vendor/foo',
            'foo',
        ];
    }

    private function createSymlink(string $target, string $link, string $cwd): void
    {
        if ('\\' === \DIRECTORY_SEPARATOR) {
            $target = str_replace('/', '\\', $target);
            $link = str_replace('/', '\\', $link);

            $isDirectory = !str_ends_with($target, '.txt');
            $command = sprintf('mklink%s "${:link}" "${:target}"', $isDirectory ? ' /d' : '');

            Process::fromShellCommandline($command, $cwd)->mustRun(null, compact('link', 'target'));
        } else {
            chdir($cwd);

            /** @phpstan-ignore-next-line because we need to create relative symlinks and cannot use the Symfony file system for that */
            symlink($target, $link);
        }
    }
}
