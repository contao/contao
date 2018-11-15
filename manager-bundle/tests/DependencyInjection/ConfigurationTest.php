<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Tests\DependencyInjection;

use Contao\CoreBundle\Tests\TestCase;
use Contao\ManagerBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Filesystem\Filesystem;

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

        $this->configuration = new Configuration($this->getTempDir());
    }

    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf(\Contao\ManagerBundle\DependencyInjection\Configuration::class, $this->configuration);

        $treeBuilder = $this->configuration->getConfigTreeBuilder();

        $this->assertInstanceOf(TreeBuilder::class, $treeBuilder);
    }

    public function testCustomManagerPath(): void
    {
        $fs = new Filesystem();
        $fs->dumpFile($this->getTempDir() . '/custom.phar.php', '');

        $params = [
            'contao_manager' => [
                'manager_path' => 'custom.phar.php'
            ]
        ];

        $configuration = (new Processor())->processConfiguration($this->configuration, $params);
        $this->assertSame('custom.phar.php', $configuration['manager_path']);
    }

    public function testExceptionIsThrownIfPathNotExists(): void
    {
        $params = [
            'contao_manager' => [
                'manager_path' => 'custom.php'
            ]
        ];

        $this->expectException(\Symfony\Component\Config\Definition\Exception\InvalidConfigurationException::class);
        (new Processor())->processConfiguration($this->configuration, $params);
    }
}
