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
use Imagine\Image\ImageInterface;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\Config\Definition\ArrayNode;
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
            [
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
            [
                'image' => [
                    'target_dir' => $windows,
                ],
            ],
        ];

        $configuration = (new Processor())->processConfiguration($this->configuration, $params);

        $this->assertSame('C:/Temp/contao', $configuration['image']['target_dir']);
    }

    public static function getPaths(): iterable
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
            [
                'upload_path' => $uploadPath,
            ],
        ];

        $this->expectException(InvalidConfigurationException::class);

        (new Processor())->processConfiguration($this->configuration, $params);
    }

    public static function getInvalidUploadPaths(): iterable
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
            [
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
            [
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

    public static function getReservedImageSizeNames(): iterable
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
            [
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
        $tree = $this->configuration->getConfigTreeBuilder()->buildTree();

        $this->assertInstanceOf(ArrayNode::class, $tree);

        $this->checkKeys($tree->getChildren());
    }

    public function testFailsIfABackendAttributeNameContainsInvalidCharacters(): void
    {
        $params = [
            [
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
            [
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
            // This first configuration should be overridden by the latter (no deep merging),
            // in order to control all the workers in your app.
            [
                'messenger' => [
                    'workers' => [
                        [
                            'transports' => ['prio_low'],
                        ],
                    ],
                ],
            ],
            [
                'messenger' => [
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
                'web_worker' => [
                    'transports' => [],
                    'grace_period' => 'PT10M',
                ],
            ],
            $configuration['messenger'],
        );

        try {
            (new Processor())->processConfiguration($this->configuration, [
                [
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
                $exception->getMessage(),
            );
        }

        try {
            (new Processor())->processConfiguration($this->configuration, [
                [
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
                $exception->getMessage(),
            );
        }
    }

    public function testFailsOnInvalidWebWorkerGracePeriod(): void
    {
        $params = [
            [
                'messenger' => [
                    'web_worker' => [
                        'grace_period' => 'nonsense',
                    ],
                ],
            ],
        ];

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid configuration for path "contao.messenger.web_worker.grace_period": Must be a valid string for \DateInterval(). "nonsense" given.');

        (new Processor())->processConfiguration($this->configuration, $params);
    }

    /**
     * @dataProvider invalidAllowedInlineStylesRegexProvider
     */
    public function testFailsOnInvalidAllowedInlineStylesRegex(string $regex, string $exceptionMessage): void
    {
        $params = [
            [
                'csp' => [
                    'allowed_inline_styles' => [
                        'text-decoration' => $regex,
                    ],
                ],
            ],
        ];

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage($exceptionMessage);

        (new Processor())->processConfiguration($this->configuration, $params);
    }

    public static function invalidAllowedInlineStylesRegexProvider(): iterable
    {
        yield [
            'te(st',
            'Invalid configuration for path "contao.csp.allowed_inline_styles": The regex "te(st" for property "text-decoration" is invalid.',
        ];

        yield [
            'te.*st',
            'Invalid configuration for path "contao.csp.allowed_inline_styles": The regex "te.*st" for property "text-decoration" contains ".*" which is not allowed due to security reasons.',
        ];
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
            [
                'cron' => [
                    'web_listener' => 'foobar',
                ],
            ],
        ];

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The value "foobar" is not allowed for path "contao.cron.web_listener". Permissible values: "auto", true, false');

        (new Processor())->processConfiguration($this->configuration, $params);
    }

    public static function cronConfigurationProvider(): iterable
    {
        yield 'Default value' => [
            [], 'auto',
        ];

        yield 'Explicit auto' => [
            [['cron' => ['web_listener' => 'auto']]], 'auto',
        ];

        yield 'Explicit false' => [
            [['cron' => ['web_listener' => false]]], false,
        ];

        yield 'Explicit true' => [
            [['cron' => ['web_listener' => true]]], true,
        ];
    }

    public function testDoesNormalizeResamplingFilter(): void
    {
        $params = [
            [
                'image' => [
                    'imagine_options' => [
                        'resampling-filter' => ImageInterface::FILTER_LANCZOS,
                    ],
                ],
            ],
        ];

        $configuration = (new Processor())->processConfiguration($this->configuration, $params);

        $this->assertArrayHasKey('resampling-filter', $configuration['image']['imagine_options']);
        $this->assertSame(ImageInterface::FILTER_LANCZOS, $configuration['image']['imagine_options']['resampling-filter']);

        $params = [
            [
                'image' => [
                    'imagine_options' => [
                        'resampling_filter' => ImageInterface::FILTER_UNDEFINED,
                    ],
                ],
            ],
        ];

        $configuration = (new Processor())->processConfiguration($this->configuration, $params);

        $this->assertArrayHasKey('resampling-filter', $configuration['image']['imagine_options']);
        $this->assertSame(ImageInterface::FILTER_UNDEFINED, $configuration['image']['imagine_options']['resampling-filter']);
    }

    /**
     * Ensure that all non-deprecated configuration keys are in lower case and
     * separated by underscores (aka snake_case).
     */
    private function checkKeys(array $configuration): void
    {
        foreach ($configuration as $key => $value) {
            if ($value instanceof ArrayNode) {
                $this->checkKeys($value->getChildren());
            }

            if ($value instanceof PrototypedArrayNode) {
                $prototype = $value->getPrototype();

                if ($prototype instanceof ArrayNode) {
                    $this->checkKeys($prototype->getChildren());
                }
            }

            if (\is_string($key) && !$value->isDeprecated() && 'resampling-filter' !== $key) {
                $this->assertMatchesRegularExpression('/^[a-z][a-z_]+[a-z]$/', $key);
            }
        }
    }
}
