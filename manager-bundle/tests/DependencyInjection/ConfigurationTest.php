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

        $this->configuration = new Configuration();
    }

    /**
     * @dataProvider getManagerPaths
     */
    public function testRegistersTheDefaultContaoManagerPath(string $file, array $params): void
    {
        $configuration = (new Processor())->processConfiguration($this->configuration, $params);

        $this->assertSame($file, $configuration['path']);
    }

    /**
     * @return array<int,array<int,string|array<string,array<string,string>>>>
     */
    public function getManagerPaths(): array
    {
        return [
            ['contao-manager.phar.php', []],
            ['custom.phar.php', ['contao_manager' => ['path' => 'custom.phar.php']]],
        ];
    }
}
