<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\DependencyInjection;

use Contao\CoreBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
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

        $this->configuration = new Configuration(false);
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\CoreBundle\DependencyInjection\Configuration', $this->configuration);

        $this->assertInstanceOf(
            'Symfony\Component\Config\Definition\Builder\TreeBuilder',
            $this->configuration->getConfigTreeBuilder()
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
        $processor = new Processor();

        $processor->processConfiguration($this->configuration, [
            'contao' => [
                'encryption_key' => 's3cr3t',
                'upload_path' => $uploadPath,
            ],
        ]);
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
            ['contao'],
            ['plugins'],
            ['share'],
            ['system'],
            ['templates'],
            ['vendor'],
            ['web'],
        ];
    }
}
