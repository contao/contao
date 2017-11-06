<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Contao;

use Contao\Combiner;
use Contao\Config;
use Contao\System;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @group contao3
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class CombinerTest extends ContaoTestCase
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $fs = new Filesystem();
        $fs->mkdir(static::getTempDir().'/assets/css');
        $fs->mkdir(static::getTempDir().'/system/tmp');
        $fs->mkdir(static::getTempDir().'/web');
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        \define('TL_ERROR', 'ERROR');
        \define('TL_ROOT', $this->getTempDir());
        \define('TL_ASSETS_URL', '');

        $this->container = new ContainerBuilder();
        $this->container->setParameter('contao.web_dir', $this->getTempDir().'/web');

        System::setContainer($this->container);
    }

    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf('Contao\Combiner', new Combiner());
    }

    public function testCombinesCssFiles(): void
    {
        file_put_contents($this->getTempDir().'/file1.css', 'file1 { background: url("foo.bar") }');
        file_put_contents($this->getTempDir().'/web/file2.css', 'web/file2');
        file_put_contents($this->getTempDir().'/file3.css', 'file3');
        file_put_contents($this->getTempDir().'/web/file3.css', 'web/file3');

        $combiner = new Combiner();
        $combiner->add('file1.css');
        $combiner->addMultiple(['file2.css', 'file3.css']);

        $this->assertSame(
            [
                'file1.css',
                'file2.css|screen',
                'file3.css|screen',
            ],
            $combiner->getFileUrls()
        );

        $combinedFile = $combiner->getCombinedFile();

        $this->assertRegExp('/^assets\/css\/file1\.css\+file2\.css\+file3\.css-[a-z0-9]+\.css$/', $combinedFile);

        $this->assertSame(
            "file1 { background: url(\"../../foo.bar\") }\n@media screen{\nweb/file2\n}\n@media screen{\nfile3\n}\n",
            file_get_contents($this->getTempDir().'/'.$combinedFile)
        );

        Config::set('debugMode', true);

        $this->assertSame(
            'file1.css"><link rel="stylesheet" href="file2.css" media="screen"><link rel="stylesheet" href="file3.css" media="screen',
            $combiner->getCombinedFile()
        );
    }

    public function testFixesTheFilePaths(): void
    {
        $class = new \ReflectionClass(Combiner::class);
        $method = $class->getMethod('fixPaths');
        $method->setAccessible(true);

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
        $method->setAccessible(true);

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
            $method->invokeArgs($class->newInstance(), [$css, ['name' => 'web/"test"/file.css']])
        );

        $expected = <<<'EOF'
test1 { background: url("../../'test'/foo.bar") }
test2 { background: url("../../'test'/foo.bar") }
test3 { background: url('../../\'test\'/foo.bar') }
EOF;

        $this->assertSame(
            $expected,
            $method->invokeArgs($class->newInstance(), [$css, ['name' => "web/'test'/file.css"]])
        );

        $expected = <<<'EOF'
test1 { background: url("../../(test)/foo.bar") }
test2 { background: url("../../(test)/foo.bar") }
test3 { background: url('../../(test)/foo.bar') }
EOF;

        $this->assertSame(
            $expected,
            $method->invokeArgs($class->newInstance(), [$css, ['name' => 'web/(test)/file.css']])
        );
    }

    public function testIgnoresDataUrlsWhileFixingTheFilePaths(): void
    {
        $class = new \ReflectionClass(Combiner::class);
        $method = $class->getMethod('fixPaths');
        $method->setAccessible(true);

        $css = <<<'EOF'
test1 { background: url('data:image/svg+xml;utf8,<svg id="foo"></svg>') }
test2 { background: url("data:image/svg+xml;utf8,<svg id='foo'></svg>") }
EOF;

        $this->assertSame(
            $css,
            $method->invokeArgs($class->newInstance(), [$css, ['name' => 'file.css']])
        );
    }

    public function testCombinesScssFiles(): void
    {
        file_put_contents($this->getTempDir().'/file1.scss', '$color: red; @import "file1_sub";');
        file_put_contents($this->getTempDir().'/file1_sub.scss', 'body { color: $color }');
        file_put_contents($this->getTempDir().'/file2.scss', 'body { color: green }');

        $combiner = new Combiner();
        $combiner->add('file1.scss');
        $combiner->add('file2.scss');

        $this->assertSame(
            [
                'assets/css/file1.scss.css',
                'assets/css/file2.scss.css',
            ],
            $combiner->getFileUrls()
        );

        $combinedFile = $combiner->getCombinedFile();

        $this->assertSame(
            "body{color:red}\nbody{color:green}\n",
            file_get_contents($this->getTempDir().'/'.$combinedFile)
        );

        Config::set('debugMode', true);

        $this->assertSame(
            'assets/css/file1.scss.css"><link rel="stylesheet" href="assets/css/file2.scss.css',
            $combiner->getCombinedFile()
        );
    }

    public function testCombinesJsFiles(): void
    {
        file_put_contents($this->getTempDir().'/file1.js', 'file1();');
        file_put_contents($this->getTempDir().'/web/file2.js', 'file2();');

        $combiner = new Combiner();
        $combiner->add('file1.js');
        $combiner->add('file2.js');

        $this->assertSame(
            [
                'file1.js',
                'file2.js',
            ],
            $combiner->getFileUrls()
        );

        $combinedFile = $combiner->getCombinedFile();

        $this->assertRegExp('/^assets\/js\/file1\.js\+file2\.js-[a-z0-9]+\.js$/', $combinedFile);
        $this->assertSame("file1();\nfile2();\n", file_get_contents($this->getTempDir().'/'.$combinedFile));

        Config::set('debugMode', true);

        $this->assertSame('file1.js"></script><script src="file2.js', $combiner->getCombinedFile());
    }
}
