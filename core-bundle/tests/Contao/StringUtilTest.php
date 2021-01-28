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

use Contao\CoreBundle\Tests\TestCase;
use Contao\StringUtil;
use Contao\System;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class StringUtilTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', $this->getFixturesDir());
        $container->set('monolog.logger.contao', new NullLogger());

        System::setContainer($container);
    }

    public function testGeneratesAliases(): void
    {
        $GLOBALS['TL_CONFIG']['characterSet'] = 'UTF-8';

        $this->assertSame('foo', StringUtil::generateAlias('foo'));
        $this->assertSame('foo', StringUtil::generateAlias('FOO'));
        $this->assertSame('foo-bar', StringUtil::generateAlias('foo bar'));
        $this->assertSame('foo-bar', StringUtil::generateAlias('%foo&bar~'));
        $this->assertSame('foo-bar', StringUtil::generateAlias('foo&amp;bar'));
        $this->assertSame('foo-bar', StringUtil::generateAlias('foo-{{link::12}}-bar'));
        $this->assertSame('id-123', StringUtil::generateAlias('123'));
        $this->assertSame('123foo', StringUtil::generateAlias('123foo'));
        $this->assertSame('foo123', StringUtil::generateAlias('foo123'));
    }

    public function testStripsTheRootDirectory(): void
    {
        $this->assertSame('', StringUtil::stripRootDir($this->getFixturesDir().'/'));
        $this->assertSame('', StringUtil::stripRootDir($this->getFixturesDir().'\\'));
        $this->assertSame('foo', StringUtil::stripRootDir($this->getFixturesDir().'/foo'));
        $this->assertSame('foo', StringUtil::stripRootDir($this->getFixturesDir().'\foo'));
        $this->assertSame('foo/', StringUtil::stripRootDir($this->getFixturesDir().'/foo/'));
        $this->assertSame('foo\\', StringUtil::stripRootDir($this->getFixturesDir().'\foo\\'));
        $this->assertSame('foo/bar', StringUtil::stripRootDir($this->getFixturesDir().'/foo/bar'));
        $this->assertSame('foo\bar', StringUtil::stripRootDir($this->getFixturesDir().'\foo\bar'));
        $this->assertSame('../../foo/bar', StringUtil::stripRootDir($this->getFixturesDir().'/../../foo/bar'));
    }

    public function testFailsIfThePathIsOutsideTheRootDirectory(): void
    {
        $this->expectException('InvalidArgumentException');

        StringUtil::stripRootDir('/foo');
    }

    public function testFailsIfThePathIsTheParentFolder(): void
    {
        $this->expectException('InvalidArgumentException');

        StringUtil::stripRootDir(\dirname($this->getFixturesDir()).'/');
    }

    public function testFailsIfThePathDoesNotMatch(): void
    {
        $this->expectException('InvalidArgumentException');

        StringUtil::stripRootDir($this->getFixturesDir().'foo/');
    }

    public function testFailsIfThePathEqualsTheRootDirectory(): void
    {
        $this->expectException('InvalidArgumentException');

        StringUtil::stripRootDir($this->getFixturesDir());
    }

    public function testHandlesFalseyValuesWhenDecodingEntities(): void
    {
        $this->assertSame('0', StringUtil::decodeEntities(0));
        $this->assertSame('0', StringUtil::decodeEntities('0'));
        $this->assertSame('', StringUtil::decodeEntities(''));
        $this->assertSame('', StringUtil::decodeEntities(false));
        $this->assertSame('', StringUtil::decodeEntities(null));
    }

    /**
     * @dataProvider trimsplitProvider
     */
    public function testSplitsAndTrimsStrings(string $pattern, string $string, array $expected): void
    {
        $this->assertSame($expected, StringUtil::trimsplit($pattern, $string));
    }

    public function trimsplitProvider(): \Generator
    {
        yield 'Test regular split' => [
            ',',
            'foo,bar',
            ['foo', 'bar'],
        ];

        yield 'Test split with trim' => [
            ',',
            " \n \r \t foo \n \r \t , \n \r \t bar \n \r \t ",
            ['foo', 'bar'],
        ];

        yield 'Test regex split' => [
            '[,;]',
            'foo,bar;baz',
            ['foo', 'bar', 'baz'],
        ];

        yield 'Test regex split with trim' => [
            '[,;]',
            " \n \r \t foo \n \r \t , \n \r \t bar \n \r \t ; \n \r \t baz \n \r \t ",
            ['foo', 'bar', 'baz'],
        ];

        yield 'Test split cache bug 1' => [
            ',',
            ',foo,,bar',
            ['', 'foo', '', 'bar'],
        ];

        yield 'Test split cache bug 2' => [
            ',,',
            'foo,,bar',
            ['foo', 'bar'],
        ];
    }
}
