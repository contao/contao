<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
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

        $this->configuration = new Configuration(false, $this->getTempDir(), $this->getTempDir().'/app', 'en');
    }

    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf('Contao\CoreBundle\DependencyInjection\Configuration', $this->configuration);

        $treeBuilder = $this->configuration->getConfigTreeBuilder();

        $this->assertInstanceOf('Symfony\Component\Config\Definition\Builder\TreeBuilder', $treeBuilder);
    }

    public function testResolvesThePaths(): void
    {
        $params = [
            'contao' => [
                'web_dir' => $this->getTempDir().'/foo/bar/../../web',
                'image' => [
                    'target_dir' => $this->getTempDir().'/foo/../assets//./images',
                ],
            ],
        ];

        $configuration = (new Processor())->processConfiguration($this->configuration, $params);

        $this->assertSame($this->getTempDir().'/web', $configuration['web_dir']);
        $this->assertSame($this->getTempDir().'/assets/images', $configuration['image']['target_dir']);
    }

    /**
     * @param string $uploadPath
     *
     * @dataProvider invalidUploadPathProvider
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

    /**
     * @return array
     */
    public function invalidUploadPathProvider(): array
    {
        return [
            [''],
            ['app'],
            ['assets'],
            ['bin'],
            ['contao'],
            ['plugins'],
            ['share'],
            ['system'],
            ['templates'],
            ['var'],
            ['vendor'],
            ['web'],
        ];
    }
}
