<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Tests\Api;

use Contao\ManagerBundle\Api\ManagerConfig;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class ManagerConfigTest extends ContaoTestCase
{
    private Filesystem $filesystem;
    private string $tempdir;
    private string $tempfile;
    private ManagerConfig $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = new Filesystem();
        $this->tempdir = $this->getTempDir();
        $this->tempfile = $this->tempdir.'/config/contao-manager.yaml';
        $this->config = new ManagerConfig($this->tempdir);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->filesystem->remove($this->tempdir);
    }

    public function testWritesConfigToFile(): void
    {
        $this->config->write(['foo' => 'bar']);

        $this->assertSame("foo: bar\n", file_get_contents($this->tempfile));
    }

    public function testReadsConfigFromFile(): void
    {
        $this->dumpTestdata(['bar' => 'foo']);

        $this->assertSame(['bar' => 'foo'], $this->config->read());
    }

    public function testReturnsEmptyConfigIfFileDoesNotExist(): void
    {
        $this->assertSame([], $this->config->read());
    }

    public function testDoesNotReadMultipleTimes(): void
    {
        $this->dumpTestdata(['bar' => 'foo']);

        $this->assertSame(['bar' => 'foo'], $this->config->all());

        $this->dumpTestdata(['foo' => 'bar']);

        $this->assertSame(['bar' => 'foo'], $this->config->all());
    }

    public function testIgnoresEmptyConfigFile(): void
    {
        $this->filesystem->dumpFile($this->tempfile, '');

        $this->assertSame([], $this->config->all());
    }

    private function dumpTestdata(array $data): void
    {
        $this->filesystem->dumpFile($this->tempfile, Yaml::dump($data));
    }
}
