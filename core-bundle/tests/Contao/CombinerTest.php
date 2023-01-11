<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Contao;

use Contao\Combiner;
use Contao\CoreBundle\Asset\ContaoContext;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Dbafs;
use Contao\Files;
use Contao\System;
use Symfony\Component\Filesystem\Filesystem;

class CombinerTest extends TestCase
{
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = new Filesystem();

        $this->filesystem->mkdir($this->getTempDir().'/assets/css');
        $this->filesystem->mkdir($this->getTempDir().'/public');
        $this->filesystem->mkdir($this->getTempDir().'/system/tmp');

        $context = $this->createMock(ContaoContext::class);
        $context
            ->method('getStaticUrl')
            ->willReturn('')
        ;

        $container = $this->getContainerWithContaoConfiguration($this->getTempDir());
        $container->setParameter('contao.web_dir', $this->getTempDir().'/public');
        $container->set('contao.assets.assets_context', $context);

        System::setContainer($container);
    }

    protected function tearDown(): void
    {
        $this->resetStaticProperties([System::class, Files::class, Dbafs::class]);

        parent::tearDown();
    }

    public function testCombinesCssFiles(): void
    {
        $this->filesystem->dumpFile($this->getTempDir().'/file1.css', 'file1 { background: url("foo.bar") }');
        $this->filesystem->dumpFile($this->getTempDir().'/public/file2.css', 'public/file2');
        $this->filesystem->dumpFile($this->getTempDir().'/file3.css', 'file3');
        $this->filesystem->dumpFile($this->getTempDir().'/public/file3.css', 'public/file3');

        $mtime = filemtime($this->getTempDir().'/file1.css');

        $combiner = new Combiner();
        $combiner->add('file1.css');
        $combiner->addMultiple(['file2.css', 'file3.css']);

        $this->assertSame(
            [
                'file1.css|'.$mtime,
                'file2.css|screen|'.$mtime,
                'file3.css|screen|'.$mtime,
            ],
            $combiner->getFileUrls()
        );

        $this->assertSame(
            [
                'https://cdn.example.com/file1.css|'.$mtime,
                'https://cdn.example.com/file2.css|screen|'.$mtime,
                'https://cdn.example.com/file3.css|screen|'.$mtime,
            ],
            $combiner->getFileUrls('https://cdn.example.com/')
        );

        $combinedFile = $combiner->getCombinedFile('https://cdn.example.com/');

        $this->assertMatchesRegularExpression('#^https://cdn.example.com/assets/css/file1\.css,file2\.css,file3\.css-[a-z0-9]+\.css$#', $combinedFile);

        $combinedFile = $combiner->getCombinedFile();

        $this->assertMatchesRegularExpression('/^assets\/css\/file1\.css,file2\.css,file3\.css-[a-z0-9]+\.css$/', $combinedFile);

        $this->assertStringEqualsFile(
            $this->getTempDir().'/'.$combinedFile,
            "file1 { background: url(\"../../foo.bar\") }\n@media screen{\npublic/file2\n}\n@media screen{\nfile3\n}\n"
        );

        System::getContainer()->setParameter('kernel.debug', true);

        $hash = substr(md5((string) $mtime), 0, 8);

        $this->assertSame(
            'file1.css?v='.$hash.'"><link rel="stylesheet" href="file2.css?v='.$hash.'" media="screen"><link rel="stylesheet" href="file3.css?v='.$hash.'" media="screen',
            $combiner->getCombinedFile()
        );
    }

    public function testFixesTheFilePaths(): void
    {
        $class = new \ReflectionClass(Combiner::class);
        $method = $class->getMethod('fixPaths');

        $css = <<<'EOF'
            test1 { background: url(foo.bar) }
            test2 { background: url("foo.bar") }
            test3 { background: url('foo.bar') }
            EOF;

        $expected = <<<'EOF'
            test1 { background: url(../../foo.bar) }
            test2 { background: url("../../foo.bar") }
            test3 { background: url('../../foo.bar') }
            EOF;

        $this->assertSame(
            $expected,
            $method->invokeArgs($class->newInstance(), [$css, ['name' => 'file.css']])
        );
    }

    public function testHandlesSpecialCharactersWhileFixingTheFilePaths(): void
    {
        $class = new \ReflectionClass(Combiner::class);
        $method = $class->getMethod('fixPaths');

        $css = <<<'EOF'
            test1 { background: url(foo.bar) }
            test2 { background: url("foo.bar") }
            test3 { background: url('foo.bar') }
            EOF;

        $expected = <<<'EOF'
            test1 { background: url("../../\"test\"/foo.bar") }
            test2 { background: url("../../\"test\"/foo.bar") }
            test3 { background: url('../../"test"/foo.bar') }
            EOF;

        $this->assertSame(
            $expected,
            $method->invokeArgs($class->newInstance(), [$css, ['name' => 'public/"test"/file.css']])
        );

        $expected = <<<'EOF'
            test1 { background: url("../../'test'/foo.bar") }
            test2 { background: url("../../'test'/foo.bar") }
            test3 { background: url('../../\'test\'/foo.bar') }
            EOF;

        $this->assertSame(
            $expected,
            $method->invokeArgs($class->newInstance(), [$css, ['name' => "public/'test'/file.css"]])
        );

        $expected = <<<'EOF'
            test1 { background: url("../../(test)/foo.bar") }
            test2 { background: url("../../(test)/foo.bar") }
            test3 { background: url('../../(test)/foo.bar') }
            EOF;

        $this->assertSame(
            $expected,
            $method->invokeArgs($class->newInstance(), [$css, ['name' => 'public/(test)/file.css']])
        );
    }

    public function testIgnoresDataUrlsWhileFixingTheFilePaths(): void
    {
        $class = new \ReflectionClass(Combiner::class);
        $method = $class->getMethod('fixPaths');

        $css = <<<'EOF'
            test1 { background: url('data:image/svg+xml;utf8,<svg id="foo"></svg>') }
            test2 { background: url("data:image/svg+xml;utf8,<svg id='foo'></svg>") }
            EOF;

        $this->assertSame(
            $css,
            $method->invokeArgs($class->newInstance(), [$css, ['name' => 'file.css']])
        );
    }

    public function testIgnoresAbsoluteUrlsWhileFixingTheFilePaths(): void
    {
        $class = new \ReflectionClass(Combiner::class);
        $method = $class->getMethod('fixPaths');

        $css = <<<'EOF'
            test1 { background: url('/path/to/file.jpg') }
            test2 { background: url(https://example.com/file.jpg) }
            test3 { background: url('#foo') }
            EOF;

        $this->assertSame(
            $css,
            $method->invokeArgs($class->newInstance(), [$css, ['name' => 'file.css']])
        );
    }

    public function testCombinesScssFiles(): void
    {
        $this->filesystem->dumpFile($this->getTempDir().'/file1.scss', '$color: red; @import "file1_sub";');
        $this->filesystem->dumpFile($this->getTempDir().'/file1_sub.scss', 'body { color: $color }');
        $this->filesystem->dumpFile($this->getTempDir().'/file2.scss', 'body { color: green }');

        $mtime1 = filemtime($this->getTempDir().'/file1.scss');
        $mtime2 = filemtime($this->getTempDir().'/file2.scss');

        $combiner = new Combiner();
        $combiner->add('file1.scss');
        $combiner->add('file2.scss');

        $this->assertSame(
            [
                'assets/css/file1.scss.css|'.$mtime1,
                'assets/css/file2.scss.css|'.$mtime2,
            ],
            $combiner->getFileUrls()
        );

        $this->assertStringEqualsFile(
            $this->getTempDir().'/'.$combiner->getCombinedFile(),
            "body{color:red}\nbody{color:green}\n"
        );

        System::getContainer()->setParameter('kernel.debug', true);

        $hash1 = substr(md5((string) $mtime1), 0, 8);
        $hash2 = substr(md5((string) $mtime2), 0, 8);

        $this->assertSame(
            'assets/css/file1.scss.css?v='.$hash1.'"><link rel="stylesheet" href="assets/css/file2.scss.css?v='.$hash2,
            $combiner->getCombinedFile()
        );
    }

    public function testCombinesJsFiles(): void
    {
        $this->filesystem->dumpFile($this->getTempDir().'/file1.js', 'file1();');
        $this->filesystem->dumpFile($this->getTempDir().'/public/file2.js', 'file2();');

        $mtime1 = filemtime($this->getTempDir().'/file1.js');
        $mtime2 = filemtime($this->getTempDir().'/public/file2.js');

        $combiner = new Combiner();
        $combiner->add('file1.js');
        $combiner->add('file2.js');

        $this->assertSame(
            [
                'file1.js|'.$mtime1,
                'file2.js|'.$mtime2,
            ],
            $combiner->getFileUrls()
        );

        $combinedFile = $combiner->getCombinedFile();

        $this->assertMatchesRegularExpression('/^assets\/js\/file1\.js,file2\.js-[a-z0-9]+\.js$/', $combinedFile);
        $this->assertStringEqualsFile($this->getTempDir().'/'.$combinedFile, "file1();\nfile2();\n");

        System::getContainer()->setParameter('kernel.debug', true);

        $hash1 = substr(md5((string) $mtime1), 0, 8);
        $hash2 = substr(md5((string) $mtime2), 0, 8);

        $this->assertSame('file1.js?v='.$hash1.'"></script><script src="file2.js?v='.$hash2, $combiner->getCombinedFile());
    }
}
