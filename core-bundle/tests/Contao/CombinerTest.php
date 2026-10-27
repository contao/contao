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
use Contao\Folder;
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

        $mtime1 = 1000000001;
        $mtime2 = 1000000002;
        $mtime3 = 1000000003;

        $this->filesystem->touch($this->getTempDir().'/file1.css', $mtime1);
        $this->filesystem->touch($this->getTempDir().'/public/file2.css', $mtime2);
        $this->filesystem->touch($this->getTempDir().'/file3.css', $mtime3);

        $combiner = new Combiner();
        $combiner->add('file1.css');
        $combiner->addMultiple(['file2.css', 'file3.css']);

        $this->assertSame(
            [
                'file1.css|'.$mtime1,
                'file2.css|screen|'.$mtime2,
                'file3.css|screen|'.$mtime3,
            ],
            $combiner->getFileUrls(),
        );

        $this->assertSame(
            [
                'https://cdn.example.com/file1.css|'.$mtime1,
                'https://cdn.example.com/file2.css|screen|'.$mtime2,
                'https://cdn.example.com/file3.css|screen|'.$mtime3,
            ],
            $combiner->getFileUrls('https://cdn.example.com/'),
        );

        $combinedFile = $combiner->getCombinedFile('https://cdn.example.com/');

        $this->assertMatchesRegularExpression('#^https://cdn.example.com/assets/css/file1\.css,file2\.css,file3\.css-[a-f0-9]{8}\.css$#', $combinedFile);

        $combinedFile = $combiner->getCombinedFile();

        $this->assertMatchesRegularExpression('/^assets\/css\/file1\.css,file2\.css,file3\.css-[a-f0-9]{8}\.css$/', $combinedFile);

        $this->assertStringEqualsFile(
            $this->getTempDir().'/'.$combinedFile,
            "file1 { background: url(\"../../foo.bar\") }\n@media screen{\npublic/file2\n}\n@media screen{\nfile3\n}\n",
        );

        System::getContainer()->setParameter('kernel.debug', true);

        $hash1 = substr(md5((string) $mtime1), 0, 8);
        $hash2 = substr(md5((string) $mtime2), 0, 8);
        $hash3 = substr(md5((string) $mtime3), 0, 8);

        $this->assertSame(
            'file1.css?v='.$hash1.'"><link rel="stylesheet" href="file2.css?v='.$hash2.'" media="screen"><link rel="stylesheet" href="file3.css?v='.$hash3.'" media="screen',
            $combiner->getCombinedFile(),
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
            $method->invokeArgs($class->newInstance(), [$css, ['name' => 'file.css']]),
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
            $method->invokeArgs($class->newInstance(), [$css, ['name' => 'public/"test"/file.css']]),
        );

        $expected = <<<'EOF'
            test1 { background: url("../../'test'/foo.bar") }
            test2 { background: url("../../'test'/foo.bar") }
            test3 { background: url('../../\'test\'/foo.bar') }
            EOF;

        $this->assertSame(
            $expected,
            $method->invokeArgs($class->newInstance(), [$css, ['name' => "public/'test'/file.css"]]),
        );

        $expected = <<<'EOF'
            test1 { background: url("../../(test)/foo.bar") }
            test2 { background: url("../../(test)/foo.bar") }
            test3 { background: url('../../(test)/foo.bar') }
            EOF;

        $this->assertSame(
            $expected,
            $method->invokeArgs($class->newInstance(), [$css, ['name' => 'public/(test)/file.css']]),
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
            $method->invokeArgs($class->newInstance(), [$css, ['name' => 'file.css']]),
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
            $method->invokeArgs($class->newInstance(), [$css, ['name' => 'file.css']]),
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
            $combiner->getFileUrls(),
        );

        $this->assertStringEqualsFile(
            $this->getTempDir().'/'.$combiner->getCombinedFile(),
            "body{color:red}\nbody{color:green}\n",
        );

        System::getContainer()->setParameter('kernel.debug', true);

        $hash1 = substr(md5((string) $mtime1), 0, 8);
        $hash2 = substr(md5((string) $mtime2), 0, 8);

        $this->assertSame(
            'assets/css/file1.scss.css?v='.$hash1.'"><link rel="stylesheet" href="assets/css/file2.scss.css?v='.$hash2,
            $combiner->getCombinedFile(),
        );
    }

    public function testDoesNotAddCharset(): void
    {
        $this->filesystem->dumpFile($this->getTempDir().'/file.scss', '.foobar { content: "ö" }');

        $combiner = new Combiner();
        $combiner->add('file.scss');

        $this->assertStringEqualsFile(
            $this->getTempDir().'/'.$combiner->getCombinedFile(),
            ".foobar{content:\"ö\"}\n",
        );
    }

    public function testHashesImportedScssContentsAfterClearingTheScriptCache(): void
    {
        $this->filesystem->dumpFile($this->getTempDir().'/file.scss', '@import "partial";');
        $this->filesystem->dumpFile($this->getTempDir().'/_partial.scss', 'body { color: red }');

        $combiner = new Combiner();
        $combiner->add('file.scss', '1');

        $originalFile = $combiner->getCombinedFile();

        $this->filesystem->dumpFile($this->getTempDir().'/_partial.scss', 'body { color: green }');

        // Cached requests keep using the compiled output until the script cache is cleared.
        $this->assertSame($originalFile, $combiner->getCombinedFile());
        $this->filesystem->remove($this->getTempDir().'/assets/css');

        $updatedFile = $combiner->getCombinedFile();

        $this->assertNotSame($originalFile, $updatedFile);
        $this->assertStringEqualsFile($this->getTempDir().'/'.$updatedFile, "body{color:green}\n");

        // Rebuilding identical contents keeps the URL stable.
        $this->filesystem->remove($this->getTempDir().'/assets/css');
        $this->assertSame($updatedFile, $combiner->getCombinedFile());

        // A missing compiled file is rebuilt with the same content hash.
        $this->filesystem->remove($this->getTempDir().'/'.$updatedFile);
        $this->assertSame($updatedFile, $combiner->getCombinedFile());
        $this->assertFileExists($this->getTempDir().'/'.$updatedFile);

        $link = $this->getTempDir().'/assets/css/file.scss-'.substr(md5('-ffile.scss-v1-mall'), 0, 8).'.css';
        $this->assertTrue(is_link($link));
        $this->assertSame(realpath($this->getTempDir().'/'.$updatedFile), realpath($link));
        $this->assertFileDoesNotExist($link.'.hash');
    }

    public function testDoesNotCompileCachedFilesAgain(): void
    {
        $this->filesystem->dumpFile($this->getTempDir().'/file.scss', 'body { color: red }');

        $combiner = $this->getMockBuilder(Combiner::class)
            ->onlyMethods(['handleScssLess'])
            ->getMock()
        ;

        $combiner
            ->expects($this->once())
            ->method('handleScssLess')
            ->willReturn('body{color:red}')
        ;

        $combiner->add('file.scss');

        $combinedFile = $combiner->getCombinedFile();

        $this->assertSame($combinedFile, $combiner->getCombinedFile());
        $this->assertSame('https://cdn.example.com/'.$combinedFile, $combiner->getCombinedFile('https://cdn.example.com/'));
    }

    public function testFindsCachedFilesInAnotherCombinerInstance(): void
    {
        $this->filesystem->dumpFile($this->getTempDir().'/file[1].css', 'body { color: red }');

        $combiner = new Combiner();
        $combiner->add('file[1].css', '1');

        $combinedFile = $combiner->getCombinedFile();

        $cachedCombiner = $this->getMockBuilder(Combiner::class)
            ->onlyMethods(['handleCss'])
            ->getMock()
        ;

        $cachedCombiner
            ->expects($this->never())
            ->method('handleCss')
        ;

        $cachedCombiner->add('file[1].css', '1');

        $this->assertSame($combinedFile, $cachedCombiner->getCombinedFile());
        $this->assertStringEndsWith('-'.substr(md5('-ffile[1].css-v1-mall-c'.md5_file($this->getTempDir().'/'.$combinedFile)), 0, 8).'.css', $combinedFile);
    }

    public function testIncludesTheFileVersionInTheContentHash(): void
    {
        $this->filesystem->dumpFile($this->getTempDir().'/file.css', 'body { color: red }');

        $combiner = new Combiner();
        $combiner->add('file.css', '1');

        $originalFile = $combiner->getCombinedFile();

        $combiner = new Combiner();
        $combiner->add('file.css', '2');

        $updatedFile = $combiner->getCombinedFile();

        $this->assertNotSame($originalFile, $updatedFile);
        $this->assertMatchesRegularExpression('/^assets\/css\/file\.css-[a-f0-9]{8}\.css$/', $updatedFile);
        $this->assertSame(file_get_contents($this->getTempDir().'/'.$originalFile), file_get_contents($this->getTempDir().'/'.$updatedFile));
    }

    public function testReplacesALegacyFileWithASymlink(): void
    {
        $this->filesystem->dumpFile($this->getTempDir().'/legacy.css', 'body { color: red }');
        $link = $this->getTempDir().'/assets/css/legacy.css-'.substr(md5('-flegacy.css-v1-mall'), 0, 8).'.css';
        $this->filesystem->dumpFile($link, 'legacy output');

        $combiner = new Combiner();
        $combiner->add('legacy.css', '1');

        $combinedFile = $combiner->getCombinedFile();

        $this->assertTrue(is_link($link));
        $this->assertSame(realpath($this->getTempDir().'/'.$combinedFile), realpath($link));
        $this->assertStringEqualsFile($this->getTempDir().'/'.$combinedFile, "body { color: red }\n");

        if ('\\' !== \DIRECTORY_SEPARATOR) {
            $this->assertSame(basename($combinedFile), $this->filesystem->readlink($link));
        }

        (new Folder('assets/css'))->purge();

        $this->assertFalse(is_link($link));
        $this->assertFileDoesNotExist($this->getTempDir().'/'.$combinedFile);
        $this->assertSame($combinedFile, $combiner->getCombinedFile());
        $this->assertTrue(is_link($link));
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
            $combiner->getFileUrls(),
        );

        $combinedFile = $combiner->getCombinedFile();

        $this->assertMatchesRegularExpression('/^assets\/js\/file1\.js,file2\.js-[a-f0-9]{8}\.js$/', $combinedFile);
        $this->assertStringEqualsFile($this->getTempDir().'/'.$combinedFile, "file1();\nfile2();\n");

        System::getContainer()->setParameter('kernel.debug', true);

        $hash1 = substr(md5((string) $mtime1), 0, 8);
        $hash2 = substr(md5((string) $mtime2), 0, 8);

        $this->assertSame('file1.js?v='.$hash1.'"></script><script src="file2.js?v='.$hash2, $combiner->getCombinedFile());
    }
}
