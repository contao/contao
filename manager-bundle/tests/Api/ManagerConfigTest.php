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
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class ManagerConfigTest extends TestCase
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $tempdir;

    /**
     * @var string
     */
    private $tempfile;

    /**
     * @var ManagerConfig
     */
    private $config;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->filesystem = new Filesystem();
        $this->tempdir = sys_get_temp_dir().'/'.uniqid('manager-config-', false);
        $this->tempfile = $this->tempdir.'/app/config/contao-manager.yml';

        $this->filesystem->mkdir($this->tempdir);

        $this->config = new ManagerConfig($this->tempdir);
    }

    /**
     * @inheritDoc
     */
    protected function tearDown()
    {
        parent::tearDown();

        $this->filesystem->remove($this->tempdir);
    }

    public function testInstantiation(): void
    {
        $this->assertInstanceOf('Contao\ManagerBundle\Api\ManagerConfig', $this->config);
    }

    public function testWritesToConfigFile()
    {
        $this->config->write(['foo' => 'bar']);

        $this->assertSame("foo: bar\n", file_get_contents($this->tempfile));
    }

    public function testReadsConfigFromFile()
    {
        $this->dumpTestdata(['bar' => 'foo']);

        $this->assertSame(['bar' => 'foo'], $this->config->read());
    }

    public function testReturnsEmptyConfigIfFileDoesNotExist()
    {
        $this->assertSame([], $this->config->read());
    }

    public function testDoesNotReadMultipleTimes()
    {
        $this->dumpTestdata(['bar' => 'foo']);

        $this->assertSame(['bar' => 'foo'], $this->config->all());

        $this->dumpTestdata(['foo' => 'bar']);

        $this->assertSame(['bar' => 'foo'], $this->config->all());
    }

    private function dumpTestdata($data)
    {
        $this->filesystem->dumpFile($this->tempfile, Yaml::dump($data));
    }
}
