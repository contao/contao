<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Image\Studio;

use Contao\Config;
use Contao\Controller;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Image\ImageFactory;
use Contao\CoreBundle\Image\LegacyResizer;
use Contao\CoreBundle\Image\PictureFactory;
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FilesModel;
use Contao\FrontendTemplate;
use Contao\Image\PictureGenerator;
use Contao\Image\ResizeCalculator;
use Contao\ImagineSvg\Imagine as ImagineSvg;
use Contao\LayoutModel;
use Contao\Model\Registry;
use Contao\PageModel;
use Contao\System;
use Contao\Template;
use Imagine\Gd\Imagine as ImagineGd;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\RequestStack;
use Webmozart\PathUtil\Path;

class FigureBuilderIntegrationTest extends TestCase
{
    /**
     * @var string
     */
    private static $testRoot;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // Make resources available in test root
        self::$testRoot = self::getTempDir();

        $filesystem = new Filesystem();

        $filesystem->symlink(
            Path::canonicalize(__DIR__.'/../../Fixtures/files'),
            Path::join(self::$testRoot, 'files')
        );

        $filesystem->symlink(
            Path::canonicalize(__DIR__.'/../../../src/Resources/contao'),
            Path::join(self::$testRoot, 'contao')
        );
    }

    /**
     * @group legacy
     *
     * @dataProvider provideControllerAddImageToTemplateTestCases
     */
    public function testControllerAddImageToTemplate(\Closure $testCase, array $expectedTemplateData): void
    {
        [$template, $dataRow, $maxWidth, $lightBoxGroupIdentifier, $filesModel] = $this->setUpTestCase($testCase);

        Controller::addImageToTemplate($template, $dataRow, $maxWidth, $lightBoxGroupIdentifier, $filesModel);

        $this->assertSameTemplateData($expectedTemplateData, $template);
    }

    /**
     * Returns test cases in the following form:
     *  [
     *     <TestCase Closure>,
     *     [
     *       expectedKey => expectedValue,
     *       ...
     *     ]
     *  ].
     *
     *  <TestCase Closure> itself must return the following preconditions and arguments:
     *   [
     *     <FilesAdapter>|<Setup Closure>|[<FilesAdapter>, <Setup Closure>]|null,
     *     [ $template, $dataRow, $maxWidth, $lightBoxGroupIdentifier, $filesModel ]
     *   ]
     */
    public function provideControllerAddImageToTemplateTestCases(): \Generator
    {
        $baseRowData = [
            'singleSRC' => 'files/public/foo.jpg',
            'size' => [150, 100, 'crop'],
            'imageTitle' => '',
            'linkTitle' => '',
            'imageUrl' => '',
            'fullsize' => '',
            'imagemargin' => '',
            'floating' => '',
            'overwriteMeta' => '',
        ];

        $baseExpectedTemplateData = [
            'width' => 200,
            'height' => 200,
            'imgSize' => ' width="150" height="100"',
            'arrSize' => [
                0 => 150,
                1 => 100,
                2 => 2,
                3 => 'width="150" height="100"',
                'bits' => 8,
                'channels' => 3,
                'mime' => 'image/jpeg',
            ],
            'picture' => [
                'img' => [
                    'width' => 150,
                    'height' => 100,
                    'hasSingleAspectRatio' => true,
                    'src' => 'assets/images/<anything>',
                    'srcset' => 'assets/images/<anything>',
                ],
                'sources' => [],
                'alt' => '',
            ],
            'src' => 'assets/images/<anything>',
            'singleSRC' => 'files/public/foo.jpg',
            'linkTitle' => '',
            'margin' => '',
            'addBefore' => true,
            'addImage' => true,
            'fullsize' => false,
        ];

        yield 'applying to FrontendTemplate' => [
            static function () use ($baseRowData) {
                return [
                    null,
                    [
                        new FrontendTemplate('ce_image'),
                        $baseRowData,
                        null,
                        null,
                        null,
                    ],
                ];
            },
            array_replace_recursive(
                $baseExpectedTemplateData,
                [
                    'picture' => [
                        'title' => '',
                    ],
                ]
            ),
        ];

        yield 'applying to \stdClass()' => [
            static function () use ($baseRowData) {
                return [
                    null,
                    [
                        new \stdClass(),
                        $baseRowData,
                        null,
                        null,
                        null,
                    ],
                ];
            },
            array_replace_recursive(
                $baseExpectedTemplateData,
                [
                    'picture' => [
                        'title' => '',
                    ],
                ]
            ),
        ];

        yield 'meta data from tl_files' => [
            function () use ($baseRowData) {
                return [
                    $this->getFilesAdapter([
                        [
                            'path' => 'files/public/foo.jpg',
                            'meta' => 'a:1:{s:2:"en";a:3:{s:5:"title";s:9:"foo title";s:3:"alt";s:7:"foo alt";s:7:"caption";s:11:"foo caption";}}',
                        ],
                    ], $filesModel),
                    [
                        new \stdClass(),
                        $baseRowData,
                        null,
                        null,
                        $filesModel,
                    ],
                ];
            },
            array_replace_recursive(
                $baseExpectedTemplateData,
                [
                    'picture' => [
                        'alt' => 'foo alt',
                        'title' => 'foo title',
                    ],
                    'alt' => 'foo alt',
                    'imageTitle' => 'foo title',
                    'imageUrl' => '',
                    'caption' => 'foo caption',
                ]
            ),
        ];

        yield 'overwriting/setting meta data (implicit)' => [
            static function () use ($baseRowData) {
                return [
                    null,
                    [
                        new \stdClass(),
                        array_merge($baseRowData, [
                            'alt' => 'a',
                            'imageTitle' => 't',
                            'caption' => 'c',
                        ]),
                        null,
                        null,
                        null,
                    ],
                ];
            },
            array_replace_recursive(
                $baseExpectedTemplateData,
                [
                    'picture' => [
                        'alt' => 'a',
                        'title' => 't',
                    ],
                ]
            ),
        ];

        yield 'overwriting/setting meta data (explicit)' => [
            function () use ($baseRowData) {
                return [
                    $this->getFilesAdapter([
                        [
                            'path' => 'files/public/foo.jpg',
                            'meta' => 'a:1:{s:2:"en";a:3:{s:5:"title";s:9:"foo title";s:3:"alt";s:7:"foo alt";s:7:"caption";s:11:"foo caption";}}',
                        ],
                    ], $filesModel),
                    [
                        new \stdClass(),
                        array_merge($baseRowData, [
                            'overwriteMeta' => '1',
                            'alt' => 'bar alt',
                            'imageTitle' => '',
                            'imageUrl' => '',
                            'caption' => 'bar caption',
                        ]),
                        null,
                        null,
                        $filesModel,
                    ],
                ];
            },
            array_replace_recursive(
                $baseExpectedTemplateData,
                [
                    'picture' => [
                        'alt' => 'bar alt',
                        'title' => '',
                    ],
                    'alt' => 'bar alt',
                    'imageTitle' => '',
                    'imageUrl' => '',
                    'caption' => 'bar caption',
                ]
            ),
        ];

        yield 'overwriting meta data with link' => [
            function () use ($baseRowData) {
                return [
                    $this->getFilesAdapter([
                        [
                            'path' => 'files/public/foo.jpg',
                            'meta' => 'a:1:{s:2:"en";a:3:{s:5:"title";s:9:"foo title";s:3:"alt";s:7:"foo alt";s:7:"caption";s:11:"foo caption";}}',
                        ],
                    ], $filesModel),
                    [
                        new \stdClass(),
                        array_merge($baseRowData, [
                            'overwriteMeta' => '1',
                            'alt' => 'bar alt',
                            'imageTitle' => 'bar title',
                            'imageUrl' => 'bar://foo',
                            'caption' => 'bar caption',
                        ]),
                        null,
                        null,
                        $filesModel,
                    ],
                ];
            },
            array_replace_recursive(
                $baseExpectedTemplateData,
                [
                    'picture' => [
                        'alt' => 'bar alt',
                    ],
                    'alt' => 'bar alt',
                    'linkTitle' => 'bar title',
                    'imageUrl' => 'bar://foo',
                    'caption' => 'bar caption',
                    'attributes' => '',
                    'href' => 'bar://foo',
                ]
            ),
        ];

        yield 'meta data from tl_files not present in current language' => [
            function () use ($baseRowData) {
                return [
                    $this->getFilesAdapter([
                        [
                            'path' => 'files/public/foo.jpg',
                            'meta' => 'a:1:{s:2:"fr";a:3:{s:5:"title";s:9:"foo title";s:3:"alt";s:7:"foo alt";s:7:"caption";s:11:"foo caption";}}',
                        ],
                    ], $filesModel),
                    [
                        new \stdClass(),
                        $baseRowData,
                        null,
                        null,
                        $filesModel,
                    ],
                ];
            },
            array_replace_recursive(
                $baseExpectedTemplateData,
                [
                    'picture' => [
                        'alt' => '',
                        'title' => '',
                    ],
                    'alt' => '',
                    'imageTitle' => '',
                    'imageUrl' => '',
                    'caption' => '',
                ]
            ),
        ];

        yield 'meta data from tl_files containing a link' => [
            function () use ($baseRowData) {
                return [
                    $this->getFilesAdapter([
                        [
                            'path' => 'files/public/foo.jpg',
                            'meta' => 'a:1:{s:2:"en";a:4:{s:5:"title";s:9:"foo title";s:3:"alt";s:7:"foo alt";s:4:"link";s:9:"foo://bar";s:7:"caption";s:11:"foo caption";}}',
                        ],
                    ], $filesModel),
                    [
                        new \stdClass(),
                        $baseRowData,
                        null,
                        null,
                        $filesModel,
                    ],
                ];
            },
            array_replace_recursive(
                $baseExpectedTemplateData,
                [
                    'picture' => [
                        'alt' => 'foo alt',
                    ],
                    'alt' => 'foo alt',
                    'linkTitle' => 'foo title',
                    'imageUrl' => 'foo://bar',
                    'caption' => 'foo caption',
                    'attributes' => '',
                    'href' => 'foo://bar',
                ]
            ),
        ];

        yield 'missing image resource' => [
            function () use ($baseRowData) {
                return [
                    $this->getFilesAdapter([
                        [
                            'path' => 'files/this/does/not/exist/foo.jpg',
                        ],
                    ], $filesModel),
                    [
                        new \stdClass(),
                        array_merge($baseRowData, ['singleSRC' => $filesModel->path]),
                        null,
                        null,
                        $filesModel,
                    ],
                ];
            },
            [
                'width' => null,
                'height' => null,
                'picture' => [
                    'img' => [
                        'src' => '',
                        'srcset' => '',
                    ],
                    'sources' => [],
                    'alt' => '',
                    'title' => '',
                ],
                'singleSRC' => 'files/this/does/not/exist/foo.jpg',
                'src' => '',
                'alt' => '',
                'caption' => '',
                'imageTitle' => '',
                'imageUrl' => '',
                'linkTitle' => '',
                'margin' => '',
                'addImage' => true,
                'addBefore' => true,
                'fullsize' => false,
            ],
        ];

        yield 'invalid singleSRC' => [
            static function () use ($baseRowData) {
                return [
                    null,
                    [
                        new \stdClass(),
                        array_merge($baseRowData, ['singleSRC' => 'this/does/not/exist/foo.jpg']),
                        null,
                        null,
                        null,
                    ],
                ];
            },
            [
                'width' => null,
                'height' => null,
                'picture' => [
                    'img' => [
                        'src' => '',
                        'srcset' => '',
                    ],
                    'sources' => [],
                    'alt' => '',
                    'title' => '',
                ],
                'singleSRC' => 'this/does/not/exist/foo.jpg',
                'src' => '',
                'linkTitle' => '',
                'margin' => '',
                'addImage' => true,
                'addBefore' => true,
                'fullsize' => false,
            ],
        ];

        yield 'margin/floating attributes' => [
            static function () use ($baseRowData) {
                return [
                    null,
                    [
                        new \stdClass(),
                        array_merge($baseRowData, [
                            'imagemargin' => serialize(['top' => 1, 'right' => 2, 'bottom' => 3, 'left' => 4, 'unit' => 'px']),
                            'floating' => 'below',
                        ]),
                        null,
                        null,
                        null,
                    ],
                ];
            },
            array_replace_recursive(
                $baseExpectedTemplateData,
                [
                    'addBefore' => false,
                    'margin' => 'margin:1px 2px 3px 4px;',
                    'floatClass' => ' float_below',
                    'picture' => [
                        'title' => '',
                    ],
                ]
            ),
        ];

        yield 'preserving existing href key' => [
            function () use ($baseRowData) {
                $template = new FrontendTemplate('ce_image');
                $template->href = 'do://not/overwrite/me';

                return [
                    $this->getFilesAdapter([
                        [
                            'path' => 'files/public/foo.jpg',
                            'meta' => 'a:1:{s:2:"en";a:4:{s:5:"title";s:9:"foo title";s:3:"alt";s:7:"foo alt";s:4:"link";s:9:"foo://bar";s:7:"caption";s:11:"foo caption";}}',
                        ],
                    ], $filesModel),
                    [
                        $template,
                        array_merge($baseRowData, [
                            'overwriteMeta' => '1',
                            'alt' => 'bar alt',
                            'imageTitle' => 'bar title',
                            'imageUrl' => '',
                            'caption' => 'bar caption',
                        ]),
                        null,
                        null,
                        $filesModel,
                    ],
                ];
            },
            array_replace_recursive(
                $baseExpectedTemplateData,
                [
                    'picture' => [
                        'alt' => 'bar alt',
                        'title' => 'bar title',
                    ],
                    'alt' => 'bar alt',
                    'imageTitle' => 'bar title',
                    'imageUrl' => '',
                    'caption' => 'bar caption',
                    'href' => 'do://not/overwrite/me',
                ]
            ),
        ];

        yield 'preserving existing href key when overwriting link' => [
            function () use ($baseRowData) {
                $template = new FrontendTemplate('ce_image');
                $template->href = 'do://not/overwrite/me';

                return [
                    $this->getFilesAdapter([
                        [
                            'path' => 'files/public/foo.jpg',
                            'meta' => 'a:1:{s:2:"en";a:4:{s:5:"title";s:9:"foo title";s:3:"alt";s:7:"foo alt";s:4:"link";s:9:"foo://bar";s:7:"caption";s:11:"foo caption";}}',
                        ],
                    ], $filesModel),
                    [
                        $template,
                        array_merge($baseRowData, [
                            'overwriteMeta' => '1',
                            'alt' => 'bar alt',
                            'imageTitle' => 'bar title',
                            'imageUrl' => 'bar://foo',
                            'caption' => 'bar caption',
                        ]),
                        null,
                        null,
                        $filesModel,
                    ],
                ];
            },
            array_replace_recursive(
                $baseExpectedTemplateData,
                [
                    'picture' => [
                        'alt' => 'bar alt',
                    ],
                    'alt' => 'bar alt',
                    'imageUrl' => 'bar://foo',
                    'caption' => 'bar caption',
                    'linkTitle' => 'bar title',
                    'imageHref' => 'bar://foo',
                    'attributes' => '',
                    'href' => 'do://not/overwrite/me',
                ]
            ),
        ];

        yield 'fullsize/lightbox with internal url (invalid image extension)' => [
            static function () use ($baseRowData) {
                return [
                    null,
                    [
                        new \stdClass(),
                        array_merge($baseRowData, [
                            'overwriteMeta' => '1',
                            'fullsize' => '1',
                            'imageUrl' => 'files/data.csv',
                            'alt' => 'a',
                            'imageTitle' => 'i',
                            'caption' => 'c',
                        ]),
                        null,
                        null,
                        null,
                    ],
                ];
            },
            array_replace_recursive(
                $baseExpectedTemplateData,
                [
                    'picture' => [
                        'alt' => 'a',
                    ],
                    'linkTitle' => 'i',
                    'href' => 'files/data.csv',
                    'attributes' => ' target="_blank"',
                    'fullsize' => true,
                ]
            ),
        ];

        yield 'fullsize/lightbox with internal url (valid image extension)' => [
            static function () use ($baseRowData) {
                return [
                    null,
                    [
                        new \stdClass(),
                        array_merge($baseRowData, [
                            'overwriteMeta' => '',
                            'fullsize' => '1',
                            'imageUrl' => 'files/public/foo.jpg',
                            'alt' => '',
                            'imageTitle' => '',
                            'caption' => '',
                        ]),
                        null,
                        null,
                        null,
                    ],
                ];
            },
            array_replace_recursive(
                $baseExpectedTemplateData,
                [
                    'lightboxPicture' => [
                        'img' => [
                            'src' => 'files/public/foo.jpg',
                            'srcset' => 'files/public/foo.jpg',
                            'hasSingleAspectRatio' => true,
                            'height' => 200,
                            'width' => 200,
                        ],
                        'sources' => [],
                    ],
                    'href' => 'files/public/foo.jpg',
                    'attributes' => ' data-lightbox=""',
                    'fullsize' => true,
                    'linkTitle' => '',
                ]
            ),
        ];

        yield 'fullsize/lightbox with external url (invalid image extension)' => [
            static function () use ($baseRowData) {
                return [
                    null,
                    [
                        new \stdClass(),
                        array_merge($baseRowData, [
                            'overwriteMeta' => '1',
                            'fullsize' => '1',
                            'imageUrl' => 'https://example.com/invalid/end.point',
                            'alt' => 'a',
                            'imageTitle' => 'i',
                            'caption' => 'c',
                        ]),
                        null,
                        null,
                        null,
                    ],
                ];
            },
            array_replace_recursive(
                $baseExpectedTemplateData,
                [
                    'picture' => [
                        'alt' => 'a',
                    ],
                    'linkTitle' => 'i',
                    'href' => 'https://example.com/invalid/end.point',
                    'attributes' => ' target="_blank" rel="noreferrer noopener"',
                    'fullsize' => true,
                ]
            ),
        ];

        yield 'fullsize/lightbox with external url (valid image extension)' => [
            static function () use ($baseRowData) {
                return [
                    null,
                    [
                        new \stdClass(),
                        array_merge($baseRowData, [
                            'overwriteMeta' => '1',
                            'fullsize' => '1',
                            'imageUrl' => 'https://example.com/valid/image.png',
                            'alt' => '',
                            'imageTitle' => 'i',
                            'caption' => '',
                        ]),
                        null,
                        null,
                        null,
                    ],
                ];
            },
            array_replace_recursive(
                $baseExpectedTemplateData,
                [
                    'href' => 'https://example.com/valid/image.png',
                    'attributes' => ' rel="noreferrer noopener" data-lightbox=""',
                    'linkTitle' => 'i',
                    'fullsize' => true,
                ]
            ),
        ];

        yield 'fullsize/lightbox with file insert tag (valid resource)' => [
            static function () use ($baseRowData) {
                return [
                    null,
                    [
                        new \stdClass(),
                        array_merge($baseRowData, [
                            'overwriteMeta' => '1',
                            'fullsize' => '1',
                            'imageUrl' => '{{file_test::files/public/bar.jpg}}',
                            'alt' => '',
                            'imageTitle' => 'i',
                            'caption' => '',
                        ]),
                        null,
                        null,
                        null,
                    ],
                ];
            },
            array_replace_recursive(
                $baseExpectedTemplateData,
                [
                    'lightboxPicture' => [
                        'img' => [
                            'src' => 'files/public/bar.jpg',
                            'srcset' => 'files/public/bar.jpg',
                            'hasSingleAspectRatio' => true,
                            'height' => 200,
                            'width' => 200,
                        ],
                        'sources' => [],
                    ],
                    'href' => 'files/public/bar.jpg',
                    'attributes' => ' data-lightbox=""',
                    'fullsize' => true,
                    'linkTitle' => 'i',
                ]
            ),
        ];

        yield 'fullsize/lightbox with file insert tag (invalid resource)' => [
            function () use ($baseRowData) {
                return [
                    $this->getFilesAdapter([
                        [
                            'path' => 'files/public/foo.jpg',
                        ],
                        [
                            'path' => 'files/this/does/not/exist/foo.jpg',
                        ],
                    ]),
                    [
                        new \stdClass(),
                        array_merge($baseRowData, [
                            'overwriteMeta' => '1',
                            'fullsize' => '1',
                            'imageUrl' => '{{file_test::files/this/does/not/exist/foo.jpg}}',
                            'imageTitle' => 'i',
                        ]),
                        null,
                        null,
                        null,
                    ],
                ];
            },
            array_replace_recursive(
                $baseExpectedTemplateData,
                [
                    'href' => 'files/this/does/not/exist/foo.jpg',
                    'attributes' => ' target="_blank"',
                    'fullsize' => true,
                    'linkTitle' => 'i',
                ]
            ),
        ];

        yield 'fullsize/lightbox with path to valid resource' => [
            static function () use ($baseRowData) {
                return [
                    null,
                    [
                        new \stdClass(),
                        array_merge($baseRowData, [
                            'overwriteMeta' => '1',
                            'fullsize' => '1',
                            'imageUrl' => 'files/public/bar.jpg',
                            'alt' => 'a',
                            'imageTitle' => 'i',
                            'caption' => 'c',
                        ]),
                        null,
                        null,
                        null,
                    ],
                ];
            },
            array_replace_recursive(
                $baseExpectedTemplateData,
                [
                    'picture' => [
                        'alt' => 'a',
                    ],
                    'lightboxPicture' => [
                        'img' => [
                            'src' => 'files/public/bar.jpg',
                            'srcset' => 'files/public/bar.jpg',
                            'hasSingleAspectRatio' => true,
                            'height' => 200,
                            'width' => 200,
                        ],
                        'sources' => [],
                    ],
                    'linkTitle' => 'i',
                    'href' => 'files/public/bar.jpg',
                    'attributes' => ' data-lightbox=""',
                    'fullsize' => true,
                ]
            ),
        ];

        yield 'fullsize/lightbox with path to valid resource (setting custom id)' => [
            static function () use ($baseRowData) {
                return [
                    null,
                    [
                        new \stdClass(),
                        array_merge($baseRowData, [
                            'overwriteMeta' => '1',
                            'fullsize' => '1',
                            'imageUrl' => 'files/public/bar.jpg',
                            'alt' => 'a',
                            'imageTitle' => 'i',
                            'caption' => 'c',
                        ]),
                        null,
                        '<custom>',
                        null,
                    ],
                ];
            },
            array_replace_recursive(
                $baseExpectedTemplateData,
                [
                    'picture' => [
                        'alt' => 'a',
                    ],
                    'lightboxPicture' => [
                        'img' => [
                            'src' => 'files/public/bar.jpg',
                            'srcset' => 'files/public/bar.jpg',
                            'hasSingleAspectRatio' => true,
                            'height' => 200,
                            'width' => 200,
                        ],
                        'sources' => [],
                    ],
                    'linkTitle' => 'i',
                    'href' => 'files/public/bar.jpg',
                    'attributes' => ' data-lightbox="<custom>"',
                    'fullsize' => true,
                ]
            ),
        ];

        yield 'fullsize/lightbox with custom size' => [
            static function () use ($baseRowData) {
                return [
                    null,
                    [
                        new \stdClass(),
                        array_merge($baseRowData, [
                            'overwriteMeta' => '1',
                            'fullsize' => '1',
                            'imageUrl' => 'files/public/bar.jpg',
                            'alt' => 'a',
                            'imageTitle' => 'i',
                            'caption' => 'c',
                            'lightboxSize' => 'a:3:{i:0;s:3:"150";i:1;s:3:"100";i:2;s:4:"crop";}',
                        ]),
                        null,
                        null,
                        null,
                    ],
                ];
            },
            array_replace_recursive(
                $baseExpectedTemplateData,
                [
                    'picture' => [
                        'alt' => 'a',
                    ],
                    'lightboxPicture' => [
                        'img' => [
                            'src' => 'assets/images/<anything>',
                            'srcset' => 'assets/images/<anything>',
                            'hasSingleAspectRatio' => true,
                            'height' => 100,
                            'width' => 150,
                        ],
                        'sources' => [],
                    ],
                    'linkTitle' => 'i',
                    'href' => 'assets/images/<anything>',
                    'attributes' => ' data-lightbox=""',
                    'fullsize' => true,
                ]
            ),
        ];

        yield 'defining max-width via config' => [
            static function () use ($baseRowData) {
                return [
                    static function (): void {
                        Config::set('maxImageWidth', 90);
                    },
                    [
                        new \stdClass(),
                        $baseRowData,
                        null,
                        null,
                        null,
                    ],
                ];
            },
            array_replace_recursive(
                $baseExpectedTemplateData,
                [
                    'arrSize' => [
                        0 => 90,
                        1 => 60,
                        3 => 'width="90" height="60"',
                    ],
                    'imgSize' => ' width="90" height="60"',
                    'picture' => [
                        'img' => [
                            'width' => 90,
                            'height' => 60,
                        ],
                        'title' => '',
                    ],
                ]
            ),
        ];

        yield 'defining max-width explicitly' => [
            static function () use ($baseRowData) {
                return [
                    null,
                    [
                        new \stdClass(),
                        $baseRowData,
                        90,
                        null,
                        null,
                    ],
                ];
            },
            array_replace_recursive(
                $baseExpectedTemplateData,
                [
                    'arrSize' => [
                        0 => 90,
                        1 => 60,
                        3 => 'width="90" height="60"',
                    ],
                    'imgSize' => ' width="90" height="60"',
                    'picture' => [
                        'img' => [
                            'width' => 90,
                            'height' => 60,
                        ],
                        'title' => '',
                    ],
                ]
            ),
        ];

        yield 'defining max-width and margin' => [
            static function () use ($baseRowData) {
                return [
                    null,
                    [
                        new \stdClass(),
                        array_merge(
                            $baseRowData,
                            [
                                'imagemargin' => serialize([
                                    'left' => 30,
                                    'right' => 30,
                                    'top' => 0,
                                    'bottom' => 0,
                                    'unit' => 'px',
                                ]),
                            ]
                        ),
                        90,
                        null,
                        null,
                    ],
                ];
            },
            array_replace_recursive(
                $baseExpectedTemplateData,
                [
                    'arrSize' => [
                        0 => 30,
                        1 => 20,
                        3 => 'width="30" height="20"',
                    ],
                    'imgSize' => ' width="30" height="20"',
                    'picture' => [
                        'img' => [
                            'width' => 30,
                            'height' => 20,
                        ],
                        'title' => '',
                    ],
                    'margin' => 'margin-right:30px;margin-left:30px;',
                ]
            ),
        ];

        yield 'setting link title fallback via title key' => [
            static function () use ($baseRowData) {
                return [
                    null,
                    [
                        new \stdClass(),
                        array_merge($baseRowData, ['title' => 'special']),
                        null,
                        null,
                        null,
                    ],
                ];
            },
            array_replace_recursive(
                $baseExpectedTemplateData,
                [
                    'picture' => [
                        'title' => '',
                    ],
                    'linkTitle' => 'special',
                ]
            ),
        ];

        yield 'image/text content element 1' => [
            function () {
                return [
                    $this->getFilesAdapter([
                        [
                            'path' => 'files/public/foo.jpg',
                        ],
                    ], $filesModel),
                    [
                        new FrontendTemplate('ce_image'),
                        [
                            'id' => '1',
                            'type' => 'image',
                            'overwriteMeta' => '',
                            'singleSRC' => 'files/public/foo.jpg',
                            'alt' => '<unrelated data>',
                            'imageTitle' => '',
                            'size' => 'a:3:{i:0;s:3:"150";i:1;s:3:"100";i:2;s:4:"crop";}',
                            'imagemargin' => '',
                            'imageUrl' => '',
                            'fullsize' => '',
                            'caption' => '<unrelated data>',
                            'floating' => 'above',
                            'linkTitle' => '',
                        ],
                        null,
                        null,
                        $filesModel,
                    ],
                ];
            },
            array_replace_recursive(
                $baseExpectedTemplateData,
                [
                    'picture' => [
                        'title' => '',
                    ],
                    'alt' => '',
                    'imageTitle' => '',
                    'imageUrl' => '',
                    'caption' => '',
                    'floatClass' => ' float_above',
                    'margin' => '',
                ]
            ),
        ];

        yield 'image/text content element 2 (overwriting meta data)' => [
            function () {
                return [
                    $this->getFilesAdapter([
                        [
                            'path' => 'files/public/foo.jpg',
                        ],
                    ], $filesModel),
                    [
                        new FrontendTemplate('ce_image'),
                        [
                            'id' => '1',
                            'type' => 'image',
                            'overwriteMeta' => '1',
                            'singleSRC' => 'files/public/foo.jpg',
                            'alt' => 'bar alt',
                            'imageTitle' => 'bar title',
                            'size' => 'a:3:{i:0;s:3:"150";i:1;s:3:"100";i:2;s:4:"crop";}',
                            'imagemargin' => '',
                            'imageUrl' => '',
                            'fullsize' => '',
                            'caption' => 'bar caption',
                            'floating' => 'above',
                            'linkTitle' => '',
                        ],
                        null,
                        null,
                        $filesModel,
                    ],
                ];
            },
            array_replace_recursive(
                $baseExpectedTemplateData,
                [
                    'picture' => [
                        'alt' => 'bar alt',
                        'title' => 'bar title',
                    ],
                    'alt' => 'bar alt',
                    'imageTitle' => 'bar title',
                    'imageUrl' => '',
                    'caption' => 'bar caption',
                    'floatClass' => ' float_above',
                    'margin' => '',
                ]
            ),
        ];

        yield 'image/text content element 3 (fullsize/lightbox without size)' => [
            function () {
                return [
                    $this->getFilesAdapter([
                        [
                            'path' => 'files/public/foo.jpg',
                            'meta' => 'a:1:{s:2:"en";a:3:{s:5:"title";s:9:"foo title";s:3:"alt";s:7:"foo alt";s:7:"caption";s:11:"foo caption";}}',
                        ],
                    ], $filesModel),
                    [
                        new FrontendTemplate('ce_image'),
                        [
                            'id' => '1',
                            'type' => 'image',
                            'overwriteMeta' => '',
                            'singleSRC' => 'files/public/foo.jpg',
                            'alt' => '',
                            'imageTitle' => '',
                            'size' => 'a:3:{i:0;s:3:"150";i:1;s:3:"100";i:2;s:4:"crop";}',
                            'imagemargin' => '',
                            'imageUrl' => '',
                            'fullsize' => '1',
                            'caption' => '',
                            'floating' => 'below',
                            'linkTitle' => '',
                        ],
                        null,
                        null,
                        $filesModel,
                    ],
                ];
            },
            array_replace_recursive(
                $baseExpectedTemplateData,
                [
                    'picture' => [
                        'alt' => 'foo alt',
                    ],
                    'lightboxPicture' => [
                        'img' => [
                            'src' => 'files/public/foo.jpg',
                            'srcset' => 'files/public/foo.jpg',
                            'hasSingleAspectRatio' => true,
                            'height' => 200,
                            'width' => 200,
                        ],
                        'sources' => [],
                    ],
                    'alt' => 'foo alt',
                    'imageUrl' => '',
                    'caption' => 'foo caption',
                    'linkTitle' => 'foo title',
                    'href' => 'files/public/foo.jpg',
                    'fullsize' => true,
                    'attributes' => ' data-lightbox=""',
                    'floatClass' => ' float_below',
                    'addBefore' => false,
                    'margin' => '',
                ]
            ),
        ];

        yield 'image/text content element 4 (lightbox + size from layout)' => [
            function () {
                return [
                    [
                        $this->getFilesAdapter([
                            [
                                'path' => 'files/public/foo.jpg',
                                'meta' => 'a:1:{s:2:"en";a:3:{s:5:"title";s:9:"foo title";s:3:"alt";s:7:"foo alt";s:7:"caption";s:11:"foo caption";}}',
                            ],
                        ], $filesModel),
                        static function (): void {
                            $GLOBALS['objPage']->layout = 1;
                            $GLOBALS['objPage']->layoutId = 1;
                        },
                    ],
                    [new FrontendTemplate('ce_image'),
                        [
                            'id' => '1',
                            'type' => 'image',
                            'overwriteMeta' => '',
                            'singleSRC' => 'files/public/foo.jpg',
                            'alt' => '',
                            'imageTitle' => '',
                            'size' => 'a:3:{i:0;s:3:"150";i:1;s:3:"100";i:2;s:4:"crop";}',
                            'imagemargin' => '',
                            'imageUrl' => '',
                            'fullsize' => '1',
                            'caption' => '',
                            'floating' => '',
                            'linkTitle' => '',
                        ],
                        null,
                        null,
                        $filesModel,
                    ],
                ];
            },
            array_replace_recursive(
                $baseExpectedTemplateData,
                [
                    'picture' => [
                        'alt' => 'foo alt',
                    ],
                    'lightboxPicture' => [
                        'img' => [
                            'src' => 'assets/images/<anything>',
                            'srcset' => 'assets/images/<anything>',
                            'hasSingleAspectRatio' => true,
                            'height' => 30,
                            'width' => 40,
                        ],
                        'sources' => [],
                    ],
                    'alt' => 'foo alt',
                    'imageUrl' => '',
                    'caption' => 'foo caption',
                    'linkTitle' => 'foo title',
                    'href' => 'assets/images/<anything>',
                    'fullsize' => true,
                    'attributes' => ' data-lightbox=""',
                    'margin' => '',
                ]
            ),
        ];

        yield 'image/text content element 5 (complex with link)' => [
            function () {
                return [
                    $this->getFilesAdapter([
                        [
                            'path' => 'files/public/foo.jpg',
                            'meta' => 'a:1:{s:2:"en";a:3:{s:5:"title";s:9:"foo title";s:3:"alt";s:7:"foo alt";s:7:"caption";s:11:"foo caption";}}',
                        ],
                    ], $filesModel),
                    [new FrontendTemplate('ce_image'),
                        [
                            'id' => '1',
                            'type' => 'image',
                            'overwriteMeta' => '1 ',
                            'singleSRC' => 'files/public/foo.jpg',
                            'alt' => 'bar alt',
                            'imageTitle' => 'bar title',
                            'size' => 'a:3:{i:0;s:3:"150";i:1;s:3:"100";i:2;s:4:"crop";}',
                            'imagemargin' => 'a:5:{s:6:"bottom";s:1:"3";s:4:"left";s:1:"4";s:5:"right";s:1:"2";s:3:"top";s:1:"1";s:4:"unit";s:2:"px";}',
                            'imageUrl' => 'https://example.com/resource',
                            'fullsize' => '1',
                            'caption' => 'bar caption',
                            'floating' => 'above',
                            'linkTitle' => '',
                        ],
                        null,
                        null,
                        $filesModel,
                    ],
                ];
            },
            array_replace_recursive(
                $baseExpectedTemplateData,
                [
                    'picture' => [
                        'alt' => 'bar alt',
                    ],
                    'alt' => 'bar alt',
                    'imageUrl' => 'https://example.com/resource',
                    'caption' => 'bar caption',
                    'linkTitle' => 'bar title',
                    'href' => 'https://example.com/resource',
                    'fullsize' => true,
                    'attributes' => ' target="_blank" rel="noreferrer noopener"',
                    'floatClass' => ' float_above',
                    'margin' => 'margin:1px 2px 3px 4px;',
                ]
            ),
        ];

        yield 'image/text content element 6 (complex with lightbox)' => [
            function () {
                return [
                    [
                        $this->getFilesAdapter([
                            [
                                'path' => 'files/public/foo.jpg',
                                'meta' => 'a:1:{s:2:"en";a:3:{s:5:"title";s:9:"foo title";s:3:"alt";s:7:"foo alt";s:7:"caption";s:11:"foo caption";}}',
                            ],
                        ], $filesModel),
                        static function (): void {
                            $GLOBALS['objPage']->layout = 1;
                            $GLOBALS['objPage']->layoutId = 1;
                        },
                    ],
                    [new FrontendTemplate('ce_image'),
                        [
                            'id' => '1',
                            'type' => 'image',
                            'overwriteMeta' => '1 ',
                            'singleSRC' => 'files/public/foo.jpg',
                            'alt' => 'bar alt',
                            'imageTitle' => 'bar title',
                            'size' => 'a:3:{i:0;s:3:"150";i:1;s:3:"100";i:2;s:4:"crop";}',
                            'imagemargin' => 'a:5:{s:6:"bottom";s:1:"3";s:4:"left";s:1:"4";s:5:"right";s:1:"2";s:3:"top";s:1:"1";s:4:"unit";s:2:"px";}',
                            'imageUrl' => '',
                            'fullsize' => '1',
                            'caption' => 'bar caption',
                            'floating' => 'above',
                            'linkTitle' => '',
                        ],
                        null,
                        null,
                        $filesModel,
                    ],
                ];
            },
            array_replace_recursive(
                $baseExpectedTemplateData,
                [
                    'picture' => [
                        'alt' => 'bar alt',
                    ],
                    'lightboxPicture' => [
                        'img' => [
                            'src' => 'assets/images/<anything>',
                            'srcset' => 'assets/images/<anything>',
                            'hasSingleAspectRatio' => true,
                            'height' => 30,
                            'width' => 40,
                        ],
                        'sources' => [],
                    ],
                    'alt' => 'bar alt',
                    'imageUrl' => '',
                    'caption' => 'bar caption',
                    'linkTitle' => 'bar title',
                    'href' => 'assets/images/<anything>',
                    'fullsize' => true,
                    'attributes' => ' data-lightbox=""',
                    'floatClass' => ' float_above',
                    'margin' => 'margin:1px 2px 3px 4px;',
                ]
            ),
        ];
    }

    public function replaceFileTestInsertTag(string $tag)
    {
        if ('file_test::files/public/bar.jpg' === $tag) {
            return 'files/public/bar.jpg';
        }

        if ('file_test::files/this/does/not/exist/foo.jpg' === $tag) {
            return 'files/this/does/not/exist/foo.jpg';
        }

        return false;
    }

    private function setUpTestCase(\Closure $testCase): array
    {
        // Evaluate preconditions and setup container
        $container = $this->getContainerWithContaoConfiguration(self::$testRoot);
        System::setContainer($container);

        [$preConditions, $arguments] = $testCase();

        $filesAdapter = null;
        $setupCallback = null;

        if ($preConditions instanceof \Closure) {
            $setupCallback = $preConditions;
        } elseif ($preConditions instanceof Adapter) {
            $filesAdapter = $preConditions;
        } elseif (\is_array($preConditions)) {
            [$filesAdapter, $setupCallback] = $preConditions;
        }

        $this->configureContainer($container, $filesAdapter);

        // Setup global environment
        Config::getInstance();

        Controller::loadLanguageFile('default');
        Controller::loadLanguageFile('tl_files');

        $GLOBALS['TL_HOOKS']['replaceInsertTags'][] = [self::class, 'replaceFileTestInsertTag'];

        // Register dummy page
        $page = $this->mockClassWithProperties(PageModel::class);
        $page->language = 'en';

        $GLOBALS['objPage'] = $page;

        // Reset config
        Config::set('maxImageWidth', 0);

        if (null !== $setupCallback) {
            $setupCallback();
        }

        return $arguments;
    }

    /**
     * Returns a files adapter capable of finding multiple files models.
     */
    private function getFilesAdapter(array $filesModelPropertyCollection, &$firstFilesModel = null)
    {
        $filesModelsByPath = [];

        foreach ($filesModelPropertyCollection as $filesModelProperties) {
            $data = array_merge([
                'id' => 1,
                'type' => 'file',
                'path' => 'files/foo',
                'importantPartX' => 0.0,
                'importantPartY' => 0.0,
                'importantPartHeight' => 0.0,
                'importantPartWidth' => 0.0,
            ], $filesModelProperties);

            // We are using a child class of FilesModel to benefit from realistic MetaData
            // creation while being able to disable object registration
            $filesModel = new class($data) extends FilesModel {
                /* @noinspection PhpMissingParentConstructorInspection */
                public function __construct($data)
                {
                    $this->setRow($data);
                }
            };

            $filesModelsByPath[$data['path']] = $filesModel;
        }

        $filesAdapter = $this->mockAdapter(['getMetaFields', 'findByPath']);
        $filesAdapter
            ->method('getMetaFields')
            ->willReturn(['title', 'alt', 'link', 'caption'])
        ;

        $filesAdapter
            ->method('findByPath')
            ->willReturnCallback(
                static function (string $path) use ($filesModelsByPath) {
                    // Allow absolute or relative paths
                    return $filesModelsByPath[Path::makeRelative($path, self::$testRoot)] ?? null;
                }
            )
        ;

        $firstFilesModel = reset($filesModelsByPath);

        return $filesAdapter;
    }

    /**
     * Returns a layout adapter that can find the default layout model.
     */
    private function getLayoutAdapter(): Adapter
    {
        $data = [
            'id' => 1,
            'lightboxSize' => 'a:3:{i:0;s:2:"40";i:1;s:2:"30";i:2;s:13:"center_center";}',
        ];

        // We are using a child class of LayoutModel so that we can push it into the
        // registry cache and therefore make it available for the legacy code
        $layoutModel = new class($data) extends LayoutModel {
            /* @noinspection PhpMissingParentConstructorInspection */
            public function __construct($data)
            {
                $this->setRow($data);
            }

            public function onRegister(Registry $registry): void
            {
                // suppress
            }
        };

        $registry = Registry::getInstance();
        $registry->reset();
        $registry->register($layoutModel);

        $layoutAdapter = $this->mockAdapter(['findByPk']);
        $layoutAdapter
            ->method('findByPk')
            ->with(1)
            ->willReturn($layoutModel)
        ;

        return $layoutAdapter;
    }

    /**
     * Configures the container with parameters and services needed for the tests.
     */
    private function configureContainer(ContainerBuilder $container, ?Adapter $filesAdapter): void
    {
        $container->setParameter('contao.image.target_dir', Path::join(self::$testRoot, 'assets/images'));
        $container->setParameter('contao.web_dir', Path::join(self::$testRoot, 'web'));
        $container->setParameter('contao.resources_paths', [Path::join(self::$testRoot, 'contao')]);

        $framework = $this->mockContaoFramework([
            FilesModel::class => $filesAdapter ?? $this->mockAdapter(['findByPath']),
            LayoutModel::class => $this->getLayoutAdapter(),
        ]);

        $resizer = new LegacyResizer($container->getParameter('contao.image.target_dir'), new ResizeCalculator());
        $resizer->setFramework($framework);

        $imageFactory = new ImageFactory(
            $resizer,
            new ImagineGd(),
            new ImagineSvg(),
            new Filesystem(),
            $framework,
            $container->getParameter('contao.image.bypass_cache'),
            $container->getParameter('contao.image.imagine_options'),
            $container->getParameter('contao.image.valid_extensions'),
            $container->getParameter('kernel.project_dir').'/'.$container->getParameter('contao.upload_path')
        );

        $pictureFactory = new PictureFactory(
            new PictureGenerator($resizer),
            $imageFactory,
            $framework,
            $container->getParameter('contao.image.bypass_cache'),
            $container->getParameter('contao.image.imagine_options')
        );

        $studio = new Studio(
            $container,
            $container->getParameter('kernel.project_dir'),
            $container->getParameter('contao.upload_path'),
            $container->getParameter('contao.image.valid_extensions')
        );

        $container->set(Studio::class, $studio);
        $container->set('contao.framework', $framework);
        $container->set('contao.image.resizer', $resizer);
        $container->set('contao.image.image_factory', $imageFactory);
        $container->set('contao.image.picture_factory', $pictureFactory);
        $container->set('request_stack', new RequestStack());
        $container->set('filesystem', new Filesystem());
        $container->set('monolog.logger.contao', new NullLogger());
    }

    private function assertSameTemplateData(array $expected, object $template): void
    {
        $templateData = $template instanceof Template
            ? $template->getData()
            : get_object_vars($template);

        $sortByKeyRecursive = static function (array &$array) use (&$sortByKeyRecursive) {
            foreach ($array as &$value) {
                if (\is_array($value)) {
                    $sortByKeyRecursive($value);
                }
            }

            return ksort($array);
        };

        $sortByKeyRecursive($expected);
        $sortByKeyRecursive($templateData);

        // Ignore generated asset paths
        array_walk_recursive(
            $templateData,
            static function (&$value): void {
                if (!\is_string($value)) {
                    return;
                }

                $value = preg_replace('#^(assets/images/)\\S*$#', '$1<anything>', $value);
            }
        );

        $this->assertSame($expected, $templateData);
    }
}
