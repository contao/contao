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
use Contao\Image\ResizeConfiguration;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\Config\Definition\ArrayNode;
use Symfony\Component\Config\Definition\BaseNode;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Definition\PrototypedArrayNode;

class ConfigurationTest extends TestCase
{
    use ExpectDeprecationTrait;

    private Configuration $configuration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configuration = new Configuration($this->getTempDir());
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
     * @group legacy
     *
     * @dataProvider getPaths
     */
    public function testResolvesThePaths(string $unix, string $windows): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.13: Setting the web directory in a config file is deprecated. Use the "extra.public-dir" config key in your root composer.json instead.');

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
        $this->assertSame('C:/Temp/contao', $configuration['image']['target_dir']);
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
        yield ['config'];
        yield ['contao'];
        yield ['plugins'];
        yield ['public'];
        yield ['share'];
        yield ['system'];
        yield ['templates'];
        yield ['var'];
        yield ['vendor'];
        yield ['web'];
    }

    public function testFailsIfAPredefinedImageSizeNameContainsOnlyDigits(): void
    {
        $params = [
            'contao' => [
                'image' => [
                    'sizes' => [
                        '123' => ['width' => 100, 'height' => 200],
                    ],
                ],
            ],
        ];

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches('/The image size name "123" cannot contain only digits/');

        (new Processor())->processConfiguration($this->configuration, $params);
    }

    /**
     * @dataProvider getReservedImageSizeNames
     */
    public function testFailsIfAPredefinedImageSizeNameIsReserved(string $name): void
    {
        $params = [
            'contao' => [
                'image' => [
                    'sizes' => [
                        $name => ['width' => 100, 'height' => 200],
                    ],
                ],
            ],
        ];

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches('/"'.$name.'" is a reserved image size name/');

        (new Processor())->processConfiguration($this->configuration, $params);
    }

    public function getReservedImageSizeNames(): \Generator
    {
        yield [ResizeConfiguration::MODE_BOX];
        yield [ResizeConfiguration::MODE_PROPORTIONAL];
        yield [ResizeConfiguration::MODE_CROP];
        yield ['left_top'];
        yield ['center_top'];
        yield ['right_top'];
        yield ['left_center'];
        yield ['center_center'];
        yield ['right_center'];
        yield ['left_bottom'];
        yield ['center_bottom'];
        yield ['right_bottom'];
    }

    public function testDeniesInvalidCrawlUris(): void
    {
        $params = [
            'contao' => [
                'crawl' => [
                    'additional_uris' => ['invalid.com'],
                ],
            ],
        ];

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid configuration for path "contao.crawl.additional_uris": All provided additional URIs must start with either http:// or https://.');

        (new Processor())->processConfiguration($this->configuration, $params);
    }

    public function testAllowsOnlySnakeCaseKeys(): void
    {
        /** @var ArrayNode $tree */
        $tree = $this->configuration->getConfigTreeBuilder()->buildTree();

        $this->assertInstanceOf(ArrayNode::class, $tree);

        $this->checkKeys($tree->getChildren());
    }

    public function testFailsIfABackendAttributeNameContainsInvalidCharacters(): void
    {
        $params = [
            'contao' => [
                'backend' => [
                    'attributes' => [
                        'data-App Name' => 'My App',
                    ],
                ],
            ],
        ];

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches('/The attribute name "data-App Name" must be a valid HTML attribute name./');

        (new Processor())->processConfiguration($this->configuration, $params);
    }

    public function testFailsOnInvalidBackupKeepIntervals(): void
    {
        $params = [
            'contao' => [
                'backup' => [
                    'keep_intervals' => [
                        'foobar',
                    ],
                ],
            ],
        ];

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid configuration for path "contao.backup.keep_intervals": ["foobar"]');

        (new Processor())->processConfiguration($this->configuration, $params);
    }

    public function testMessengerConfiguration(): void
    {
        $params = [
            'contao' => [
                'messenger' => [
                    'console_path' => '%kernel.project_dir%/vendor/bin/contao-console',
                    'workers' => [
                        [
                            'transports' => ['prio_low'],
                        ],
                        [
                            'transports' => ['prio_normal'],
                            'options' => ['--sleep=10', '--time-limit=60'],
                            'autoscale' => [
                                'desired_size' => 10,
                                'max' => 20,
                            ],
                        ],
                        [
                            'transports' => ['prio_high'],
                            'options' => ['--sleep=5', '--time-limit=60'],
                            'autoscale' => [
                                'desired_size' => 5,
                                'max' => 30,
                                'min' => 4,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $configuration = (new Processor())->processConfiguration($this->configuration, $params);

        $this->assertSame(
            [
                'console_path' => '%kernel.project_dir%/vendor/bin/contao-console',
                'workers' => [
                    [
                        'transports' => ['prio_low'],
                        'options' => ['--time-limit=60'],
                        'autoscale' => [
                            'enabled' => false,
                            'min' => 1,
                        ],
                    ],
                    [
                        'transports' => ['prio_normal'],
                        'options' => ['--sleep=10', '--time-limit=60'],
                        'autoscale' => [
                            'desired_size' => 10,
                            'max' => 20,
                            'enabled' => true,
                            'min' => 1,
                        ],
                    ],
                    [
                        'transports' => ['prio_high'],
                        'options' => ['--sleep=5', '--time-limit=60'],
                        'autoscale' => [
                            'desired_size' => 5,
                            'max' => 30,
                            'min' => 4,
                            'enabled' => true,
                        ],
                    ],
                ],
            ],
            $configuration['messenger']
        );

        try {
            (new Processor())->processConfiguration($this->configuration, [
                'contao' => [
                    'messenger' => [
                        'workers' => [
                            [
                                'transports' => ['prio_normal'],
                                'options' => ['--sleep=10', '--time-limit=60'],
                                'autoscale' => [
                                    'enabled' => true,
                                ],
                            ],
                        ],
                    ],
                ],
            ]);
        } catch (InvalidConfigurationException $exception) {
            $this->assertStringContainsString(
                'The child config "desired_size" under "contao.messenger.workers.0.autoscale" must be configured',
                $exception->getMessage()
            );
        }

        try {
            (new Processor())->processConfiguration($this->configuration, [
                'contao' => [
                    'messenger' => [
                        'workers' => [
                            [
                                'transports' => ['prio_normal'],
                                'options' => ['--sleep=10', '--time-limit=60'],
                                'autoscale' => [
                                    'enabled' => true,
                                    'desired_size' => 10,
                                ],
                            ],
                        ],
                    ],
                ],
            ]);
        } catch (InvalidConfigurationException $exception) {
            $this->assertStringContainsString(
                'The child config "max" under "contao.messenger.workers.0.autoscale" must be configured',
                $exception->getMessage()
            );
        }
    }

    /**
     * @dataProvider cronConfigurationProvider
     */
    public function testValidCronConfiguration(array $params, bool|string $expected): void
    {
        $configuration = (new Processor())->processConfiguration($this->configuration, $params);

        $this->assertSame($expected, $configuration['cron']['web_listener']);
    }

    public function testInvalidCronConfiguration(): void
    {
        $params = [
            'contao' => [
                'cron' => [
                    'web_listener' => 'foobar',
                ],
            ],
        ];

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The value "foobar" is not allowed for path "contao.cron.web_listener". Permissible values: "auto", true, false');

        (new Processor())->processConfiguration($this->configuration, $params);
    }

    public function cronConfigurationProvider(): \Generator
    {
        yield 'Default value' => [
            [], 'auto',
        ];

        yield 'Explicit auto' => [
            ['contao' => ['cron' => ['web_listener' => 'auto']]], 'auto',
        ];

        yield 'Explicit false' => [
            ['contao' => ['cron' => ['web_listener' => false]]], false,
        ];

        yield 'Explicit true' => [
            ['contao' => ['cron' => ['web_listener' => true]]], true,
        ];
    }

    /**
     * Ensure that all non-deprecated configuration keys are in lower case and
     * separated by underscores (aka snake_case).
     */
    private function checkKeys(array $configuration): void
    {
        /** @var BaseNode $value */
        foreach ($configuration as $key => $value) {
            if ($value instanceof ArrayNode) {
                $this->checkKeys($value->getChildren());
            }

            /** @var ArrayNode $prototype */
            if ($value instanceof PrototypedArrayNode && ($prototype = $value->getPrototype()) instanceof ArrayNode) {
                $this->checkKeys($prototype->getChildren());
            }

            if (\is_string($key) && !$value->isDeprecated()) {
                $this->assertMatchesRegularExpression('/^[a-z][a-z_]+[a-z]$/', $key);
            }
        }
    }
}
