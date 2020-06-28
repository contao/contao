<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Functional;

use Contao\Config;
use Contao\ContentModel;
use Contao\Controller;
use Contao\FilesModel;
use Contao\FrontendTemplate;
use Contao\PageModel;
use Contao\Template;
use Contao\TestCase\FunctionalTestCase;
use Symfony\Component\Filesystem\Filesystem;

class ContaoFrameworkControllerTest extends FunctionalTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        static::bootKernel();
        static::resetDatabaseSchema();

        // Make demo resources available in the upload path
        (new Filesystem())->symlink(__DIR__.'/../Fixtures/files', __DIR__.'/../../var/files');
    }

    protected function setUp(): void
    {
        parent::setUp();

        \define('TL_MODE', 'FE');

        static::bootKernel();

        // Register replacement for file insert tag (real UUIDs currently aren't supported by our fixture loader)
        $GLOBALS['TL_HOOKS']['replaceInsertTags'][] = [self::class, 'replaceFileTestInsertTag'];
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_HOOKS'], $GLOBALS['TL_CONFIG'], $GLOBALS['objPage']);

        static::$container->get('contao.framework')->reset();

        parent::tearDown();
    }

    /**
     * @dataProvider provideImageConfigurations
     *
     * @group legacy
     */
    public function testAddImageToTemplate(array $databaseFixtures, \Closure $argumentCallback, array $expectedTemplateData): void
    {
        static::loadFixtures(
            array_map(
                static function (string $fixture): string {
                    return __DIR__."/../Fixtures/Functional/Controller/Image/$fixture.yml";
                },
                $databaseFixtures
            )
        );

        [$template, $dataRow, $maxWidth, $lightBoxGroupIdentifier, $filesModel] = $argumentCallback();

        Controller::addImageToTemplate($template, $dataRow, $maxWidth, $lightBoxGroupIdentifier, $filesModel);

        $this->assertSameTemplateData($expectedTemplateData, $template);
    }

    /**
     * @dataProvider provideImageConfigurations
     */
    public function testAddImageToTemplateNew(array $databaseFixtures, \Closure $argumentCallback, array $expectedTemplateData): void
    {
        static::loadFixtures(
            array_map(
                static function (string $fixture): string {
                    return __DIR__."/../Fixtures/Functional/Controller/Image/$fixture.yml";
                },
                $databaseFixtures
            )
        );

        [$template, $dataRow, $maxWidth, $lightBoxGroupIdentifier, $filesModel] = $argumentCallback();

        Controller::addImageToTemplate_new($template, $dataRow, $maxWidth, $lightBoxGroupIdentifier, $filesModel);

        $this->assertSameTemplateData($expectedTemplateData, $template);
    }

    public function provideImageConfigurations(): \Generator
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
            ['folder', 'image-file-1'],
            static function () use ($baseRowData) {
                return [
                    new FrontendTemplate('ce_image'),
                    $baseRowData,
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
            ['folder', 'image-file-1'],
            static function () use ($baseRowData) {
                return [
                    new \stdClass(),
                    $baseRowData,
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
            ['folder', 'image-file-3-with-metadata', 'root-and-regular-page_en'],
            function () use ($baseRowData) {
                $this->loadGlobalObjPage(2);

                return [
                    new \stdClass(),
                    $baseRowData,
                    null,
                    null,
                    FilesModel::findById(3),
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
            ['folder', 'image-file-1'],
            static function () use ($baseRowData) {
                return [
                    new \stdClass(),
                    array_merge($baseRowData, [
                        'alt' => 'a',
                        'imageTitle' => 't',
                        'caption' => 'c',
                    ]),
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

        yield 'overwriting meta data (explicit)' => [
            ['folder', 'image-file-3-with-metadata', 'root-and-regular-page_en'],
            function () use ($baseRowData) {
                $this->loadGlobalObjPage(2);

                return [
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
                    FilesModel::findById(3),
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
            ['folder', 'image-file-3-with-metadata', 'root-and-regular-page_en'],
            function () use ($baseRowData) {
                $this->loadGlobalObjPage(2);

                return [
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
                    FilesModel::findById(3),
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
            ['folder', 'image-file-3-with-metadata', 'root-and-regular-page_fr'],
            function () use ($baseRowData) {
                $this->loadGlobalObjPage(2);

                return [
                    new \stdClass(),
                    $baseRowData,
                    null,
                    null,
                    FilesModel::findById(3),
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
            ['folder', 'image-file-4-with-metadata-containing-link', 'root-and-regular-page_en'],
            function () use ($baseRowData) {
                $this->loadGlobalObjPage(2);

                return [
                    new \stdClass(),
                    $baseRowData,
                    null,
                    null,
                    FilesModel::findById(4),
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
            ['folder', 'image-file-5-with-missing-resource'],
            static function () use ($baseRowData) {
                $filesModel = FilesModel::findById(5);

                return [
                    new \stdClass(),
                    array_merge($baseRowData, ['singleSRC' => $filesModel->path]),
                    null,
                    null,
                    $filesModel,
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
            [],
            static function () use ($baseRowData) {
                return [
                    new \stdClass(),
                    array_merge($baseRowData, ['singleSRC' => 'this/does/not/exist/foo.jpg']),
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
            [],
            static function () use ($baseRowData) {
                return [
                    new \stdClass(),
                    array_merge($baseRowData, [
                        'imagemargin' => serialize(['top' => 1, 'right' => 2, 'bottom' => 3, 'left' => 4, 'unit' => 'px']),
                        'floating' => 'below',
                    ]),
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
            ['folder', 'image-file-4-with-metadata-containing-link', 'root-and-regular-page_en'],
            function () use ($baseRowData) {
                $this->loadGlobalObjPage(2);

                $template = new FrontendTemplate('ce_image');
                $template->href = 'do://not/overwrite/me';

                return [
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
                    FilesModel::findById(4),
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
            ['folder', 'image-file-4-with-metadata-containing-link', 'root-and-regular-page_en'],
            function () use ($baseRowData) {
                $this->loadGlobalObjPage(2);

                $template = new FrontendTemplate('ce_image');
                $template->href = 'do://not/overwrite/me';

                return [
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
                    FilesModel::findById(4),
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

        yield 'fullsize/lightbox with external url (invalid image extension)' => [
            ['folder', 'image-file-1'],
            static function () use ($baseRowData) {
                return [
                    new \stdClass(),
                    array_merge($baseRowData, [
                        'overwriteMeta' => '1',
                        'fullsize' => '1',
                        'imageUrl' => 'https://example.com/invalid/end.point',
                        'alt' => 'a',
                        'imageTitle' => 'i',
                        'caption' => 'c',
                    ]),
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
            ['folder', 'image-file-1'],
            static function () use ($baseRowData) {
                return [
                    new \stdClass(),
                    array_merge($baseRowData, [
                        'overwriteMeta' => '1',
                        'fullsize' => '1',
                        'imageUrl' => 'https://example.com/valid/image.png',
                        'alt' => '',
                        'imageTitle' => 'i',
                        'caption' => '',
                    ]),
                ];
            },
            array_replace_recursive(
                $baseExpectedTemplateData,
                [
                    'href' => 'https://example.com/valid/image.png',
                    'attributes' => ' rel="noreferrer noopener" data-lightbox="<anything>"',
                    'linkTitle' => 'i',
                    'fullsize' => true,
                ]
            ),
        ];

        yield 'fullsize/lightbox with file insert tag (valid resource)' => [
            ['folder', 'image-file-2'],
            static function () use ($baseRowData) {
                return [
                    new \stdClass(),
                    array_merge($baseRowData, [
                        'overwriteMeta' => '1',
                        'fullsize' => '1',
                        'imageUrl' => '{{file_test::files/public/bar.jpg}}',
                        'alt' => '',
                        'imageTitle' => 'i',
                        'caption' => '',
                    ]),
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
                    'attributes' => ' data-lightbox="<anything>"',
                    'fullsize' => true,
                    'linkTitle' => 'i',
                ]
            ),
        ];

        yield 'fullsize/lightbox with file insert tag (invalid resource)' => [
            ['folder', 'image-file-1', 'image-file-5-with-missing-resource'],
            static function () use ($baseRowData) {
                return [
                    new \stdClass(),
                    array_merge($baseRowData, [
                        'overwriteMeta' => '1',
                        'fullsize' => '1',
                        'imageUrl' => '{{file_test::files/this/does/not/exist/foo.jpg}}',
                        'alt' => '',
                        'imageTitle' => 'i',
                        'caption' => '',
                    ]),
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
            ['folder', 'image-file-1'],
            static function () use ($baseRowData) {
                return [
                    new \stdClass(),
                    array_merge($baseRowData, [
                        'overwriteMeta' => '1',
                        'fullsize' => '1',
                        'imageUrl' => 'files/public/bar.jpg',
                        'alt' => 'a',
                        'imageTitle' => 'i',
                        'caption' => 'c',
                    ]),
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
                    'attributes' => ' data-lightbox="<anything>"',
                    'fullsize' => true,
                ]
            ),
        ];

        yield 'fullsize/lightbox with path to valid resource (overwriting id)' => [
            ['folder', 'image-file-1'],
            static function () use ($baseRowData) {
                return [
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

        yield 'defining max-width via config' => [
            ['folder', 'image-file-1'],
            static function () use ($baseRowData) {
                Config::set('maxImageWidth', 90);

                return [
                    new \stdClass(),
                    $baseRowData,
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
            ['folder', 'image-file-1'],
            static function () use ($baseRowData) {
                return [
                    new \stdClass(),
                    $baseRowData,
                    90,
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
            ['folder', 'image-file-1'],
            static function () use ($baseRowData) {
                return [
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
            ['folder', 'image-file-1'],
            static function () use ($baseRowData) {
                return [
                    new \stdClass(),
                    array_merge($baseRowData, ['title' => 'special']),
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

        yield 'image content element 1' => [
            ['ce_image', 'folder', 'image-file-1', 'root-and-regular-page_en'],
            function () {
                $this->loadGlobalObjPage(2);
                [$rowData, $filesModel] = $this->getContentElementData(1);

                return [
                    new FrontendTemplate('ce_image'),
                    $rowData,
                    null,
                    null,
                    $filesModel,
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

        yield 'image content element 2 (overwriting meta data)' => [
            ['ce_image-with-metadata', 'folder', 'image-file-1', 'root-and-regular-page_en'],
            function () {
                $this->loadGlobalObjPage(2);
                [$rowData, $filesModel] = $this->getContentElementData(1);

                return [
                    new FrontendTemplate('ce_image'),
                    $rowData,
                    null,
                    null,
                    $filesModel,
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

        yield 'image content element 3 (fullsize/lightbox without size)' => [
            ['ce_image-with-fullsize', 'folder', 'image-file-3-with-metadata', 'root-and-regular-page_en'],
            function () {
                $this->loadGlobalObjPage(2);
                [$rowData, $filesModel] = $this->getContentElementData(1);

                return [
                    new FrontendTemplate('ce_image'),
                    $rowData,
                    null,
                    null,
                    $filesModel,
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
                    'attributes' => ' data-lightbox="<anything>"',
                    'floatClass' => ' float_above',
                    'margin' => '',
                ]
            ),
        ];

        yield 'image content element 4 (lightbox + size from layout)' => [
            ['ce_image-with-fullsize', 'folder', 'image-file-3-with-metadata', 'root-and-regular-page_en', 'layout-with-lightbox-size'],
            function () {
                $this->loadGlobalObjPage(2);
                [$rowData, $filesModel] = $this->getContentElementData(1);

                return [
                    new FrontendTemplate('ce_image'),
                    $rowData,
                    null,
                    null,
                    $filesModel,
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
                    'attributes' => ' data-lightbox="<anything>"',
                    'floatClass' => ' float_above',
                    'margin' => '',
                ]
            ),
        ];

        yield 'image content element 5 (complex with link)' => [
            ['ce_image-complex', 'folder', 'image-file-3-with-metadata', 'root-and-regular-page_en'],
            function () {
                $this->loadGlobalObjPage(2);
                [$rowData, $filesModel] = $this->getContentElementData(1);

                return [
                    new FrontendTemplate('ce_image'),
                    $rowData,
                    null,
                    null,
                    $filesModel,
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

        yield 'image content element 6 (complex with lightbox)' => [
            ['ce_image-complex-with-lightbox', 'folder', 'image-file-3-with-metadata', 'root-and-regular-page_en', 'layout-with-lightbox-size'],
            function () {
                $this->loadGlobalObjPage(2);
                [$rowData, $filesModel] = $this->getContentElementData(1);

                return [
                    new FrontendTemplate('ce_image'),
                    $rowData,
                    null,
                    null,
                    $filesModel,
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
                    'attributes' => ' data-lightbox="<anything>"',
                    'floatClass' => ' float_above',
                    'margin' => 'margin:1px 2px 3px 4px;',
                ]
            ),
        ];
    }

    public function replaceFileTestInsertTag(string $tag)
    {
        $parts = explode('::', $tag);

        if ('file_test' !== $parts[0]) {
            return false;
        }

        $filesModel = FilesModel::findByPath($parts[1]);

        return null !== $filesModel ? $filesModel->path : false;
    }

    private function loadGlobalObjPage(int $id): void
    {
        global $objPage;

        $objPage = PageModel::findById($id);

        $objPage->loadDetails();
        $objPage->layoutId = $objPage->layout;
    }

    private function getContentElementData(int $id): array
    {
        $rowData = ContentModel::findById($id)->row();

        // uuid == hash in our test data / working around the fact that we did not set real UUIDs
        $filesModel = FilesModel::findOneByHash($rowData['singleSRC']);
        $rowData['singleSRC'] = $filesModel->path;

        return [$rowData, $filesModel];
    }

    private function assertSameTemplateData(array $expected, object $template): void
    {
        $templateData = $template instanceof Template ?
            $template->getData() : get_object_vars($template);

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

        // Ignore generated asset paths + light box identifiers
        array_walk_recursive(
            $templateData,
            static function (&$value): void {
                if (!\is_string($value)) {
                    return;
                }

                $value = preg_replace('#^(assets/images/)\S*$#', '$1<anything>', $value);
                $value = preg_replace('#(data-lightbox=)"(?!<custom>")\S*"#', '$1"<anything>"', $value);
            }
        );

        $this->assertSame($expected, $templateData);
    }
}
