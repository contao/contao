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
use Symfony\Component\Filesystem\Filesystem;

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

    public function testAdjustsTheDefaultWebDir(): void
    {
        $configuration = (new Processor())->processConfiguration($this->configuration, []);

        $this->assertSame('public', basename($configuration['web_dir']));

        (new Filesystem())->mkdir($this->getTempDir().'/web');

        $configuration = (new Processor())->processConfiguration($this->configuration, []);

        $this->assertSame('web', basename($configuration['web_dir']));
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

    public function testFailsIfAFilesystemBucketDoesNotDefineAPath(): void
    {
        $params = [
            'contao' => [
                'virtual_filesystem' => [
                    'buckets' => [
                        'foo' => [
                        ],
                    ],
                ],
            ],
        ];

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches('/Cannot auto configure the "local" adapter for filesystem bucket "foo"\. You must at least define a "mount_path" or configure a directory under "contao\.virtual_filesystem\.foo\.storage\.options\.directory"/');

        (new Processor())->processConfiguration($this->configuration, $params);
    }

    /**
     * @dataProvider getInvalidFilesystemBucketNames
     */
    public function testFailsIfAFilesystemBucketHasAnInvalidName(string $name, string $exception): void
    {
        $params = [
            'contao' => [
                'virtual_filesystem' => [
                    'buckets' => [
                        $name => [
                            'mount_path' => 'foo',
                        ],
                    ],
                ],
            ],
        ];

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches("/$exception/");

        (new Processor())->processConfiguration($this->configuration, $params);
    }

    public function getInvalidFilesystemBucketNames(): \Generator
    {
        yield 'contains only digits' => [
            '123', 'The filesystem bucket name "123" cannot contain only digits',
        ];

        yield 'contains invalid characters' => [
            'foo&bar', 'The filesystem bucket name "foo&bar" must consist of lowercase letters, digits and underscores only',
        ];
    }

    public function testSetsTheLocalAdapterDefaultPathForFilesystemBuckets(): void
    {
        $params = [
            'contao' => [
                'virtual_filesystem' => [
                    'buckets' => [
                        'files' => [
                            'mount_path' => 'foo',
                        ],
                    ],
                ],
            ],
        ];

        $configuration = (new Processor())->processConfiguration($this->configuration, $params);

        $this->assertSame(
            [
                'adapter' => 'local',
                'options' => [
                    'directory' => '%kernel.project_dir%/foo',
                ],
                'visibility' => null,
                'case_sensitive' => true,
                'disable_asserts' => false,
            ],
            $configuration['virtual_filesystem']['buckets']['files']['storage']
        );

        $this->assertSame('foo', $configuration['virtual_filesystem']['buckets']['files']['mount_path']);
        $this->assertFalse($configuration['virtual_filesystem']['buckets']['files']['dbafs']['enabled']);
    }

    public function testSetsTheDbafsTableForFilesystemBuckets(): void
    {
        $params = [
            'contao' => [
                'virtual_filesystem' => [
                    'buckets' => [
                        'foobar' => [
                            'mount_path' => 'files/foobar',
                            'dbafs' => null,
                        ],
                    ],
                ],
            ],
        ];

        $configuration = (new Processor())->processConfiguration($this->configuration, $params);

        $this->assertSame(
            [
                'enabled' => true,
                'table' => 'tl_foobar',
                'hash_algorithm' => 'md5',
                'max_file_size' => 2147483648,
                'bulk_insert_size' => 100,
                'use_last_modified' => false,
            ],
            $configuration['virtual_filesystem']['buckets']['foobar']['dbafs']
        );
    }

    /**
     * @dataProvider getFileSizes
     */
    public function testParsesTheDbafsMaxFileSizeConfiguration(string $value, int $resolvedValue): void
    {
        $params = [
            'contao' => [
                'virtual_filesystem' => [
                    'buckets' => [
                        'foobar' => [
                            'mount_path' => 'files/foobar',
                            'dbafs' => [
                                'enabled' => true,
                                'max_file_size' => $value,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $configuration = (new Processor())->processConfiguration($this->configuration, $params);

        $this->assertSame($resolvedValue, $configuration['virtual_filesystem']['buckets']['foobar']['dbafs']['max_file_size']);
    }

    public function getFileSizes(): \Generator
    {
        yield 'number in bytes' => [
            '1000000', 1_000_000,
        ];

        yield 'with m magnitude postfix' => [
            '40m', 40 * 1000 * 1000,
        ];

        yield 'with Gi magnitude postfix' => [
            '1.5Gi', (int) (1.5 * 1024 * 1024 * 1024),
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
                $this->assertRegExp('/^[a-z][a-z_]+[a-z]$/', $key);
            }
        }
    }
}
