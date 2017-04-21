<?php

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
    protected function setUp()
    {
        parent::setUp();

        $this->configuration = new Configuration(false, $this->getRootDir().'/app');
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\CoreBundle\DependencyInjection\Configuration', $this->configuration);

        $treeBuilder = $this->configuration->getConfigTreeBuilder();

        $this->assertInstanceOf('Symfony\Component\Config\Definition\Builder\TreeBuilder', $treeBuilder);
    }

    /**
     * Tests the path resolving.
     */
    public function testPathResolving()
    {
        $params = [
            'contao' => [
                'root_dir' => $this->getRootDir().'/foo',
                'web_dir' => $this->getRootDir().'/foo/../web',
                'image' => [
                    'target_dir' => $this->getRootDir().'/foo/../assets/images',
                ],
            ],
        ];

        $configuration = (new Processor())->processConfiguration($this->configuration, $params);

        $this->assertEquals(strtr($this->getRootDir().'/foo', '/', DIRECTORY_SEPARATOR), $configuration['root_dir']);
        $this->assertEquals(strtr($this->getRootDir().'/web', '/', DIRECTORY_SEPARATOR), $configuration['web_dir']);

        $this->assertEquals(
            strtr($this->getRootDir().'/assets/images', '/', DIRECTORY_SEPARATOR),
            $configuration['image']['target_dir']
        );
    }

    /**
     * Tests an invalid upload path.
     *
     * @param string $uploadPath
     *
     * @dataProvider invalidUploadPathProvider
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     */
    public function testInvalidUploadPath($uploadPath)
    {
        $params = [
            'contao' => [
                'encryption_key' => 's3cr3t',
                'upload_path' => $uploadPath,
            ],
        ];

        (new Processor())->processConfiguration($this->configuration, $params);
    }

    /**
     * Provides the data for the testInvalidUploadPath() method.
     *
     * @return array
     */
    public function invalidUploadPathProvider()
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
