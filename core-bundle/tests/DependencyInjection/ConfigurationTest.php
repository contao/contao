<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\DependencyInjection;

use Contao\CoreBundle\DependencyInjection\Configuration;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->configuration = new Configuration($this->getTempDir(), 'en');
    }

    public function testAddsTheImagineService(): void
    {
        $params = [];
        $configuration = (new Processor())->processConfiguration($this->configuration, $params);

        $this->assertNull($configuration['image']['imagine_service']);

        $params = [
            'contao' => [
                'image' => [
                    'imagine_service' => 'my_super_service',
                ],
            ],
        ];

        $configuration = (new Processor())->processConfiguration($this->configuration, $params);

        $this->assertSame('my_super_service', $configuration['image']['imagine_service']);
    }

    /**
     * @dataProvider getPaths
     */
    public function testResolvesThePaths(string $unix, string $windows): void
    {
        $params = [
            'contao' => [
                'web_dir' => $unix,
                'image' => [
                    'target_dir' => $windows,
                ],
            ],
        ];

        $configuration = (new Processor())->processConfiguration($this->configuration, $params);

        $this->assertSame('/tmp/contao', $configuration['web_dir']);
        $this->assertSame('C:\Temp\contao', $configuration['image']['target_dir']);
    }

    public function getPaths(): \Generator
    {
        yield ['/tmp/contao', 'C:\Temp\contao'];
        yield ['/tmp/foo/../contao', 'C:\Temp\foo\..\contao'];
        yield ['/tmp/foo/bar/../../contao', 'C:\Temp\foo\bar\..\..\contao'];
        yield ['/tmp/./contao', 'C:\Temp\.\contao'];
        yield ['/tmp//contao', 'C:\Temp\\\\contao'];
        yield ['/tmp/contao/', 'C:\Temp\contao\\'];
        yield ['/tmp/contao/.', 'C:\Temp\contao\.'];
        yield ['/tmp/contao/foo/..', 'C:\Temp\contao\foo\..'];
    }

    /**
     * @dataProvider getInvalidUploadPaths
     */
    public function testFailsIfTheUploadPathIsInvalid(string $uploadPath): void
    {
        $params = [
            'contao' => [
                'encryption_key' => 's3cr3t',
                'upload_path' => $uploadPath,
            ],
        ];

        $this->expectException(InvalidConfigurationException::class);

        (new Processor())->processConfiguration($this->configuration, $params);
    }

    public function getInvalidUploadPaths(): \Generator
    {
        yield [''];
        yield ['app'];
        yield ['assets'];
        yield ['bin'];
        yield ['contao'];
        yield ['plugins'];
        yield ['share'];
        yield ['system'];
        yield ['templates'];
        yield ['var'];
        yield ['vendor'];
        yield ['web'];
    }
}
