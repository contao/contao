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

use Contao\Controller;
use Contao\FilesModel;
use Contao\FrontendTemplate;
use Contao\PageModel;
use Contao\Template;
use Contao\TestCase\FunctionalTestCase;
use Model\Registry;

class ContaoFrameworkControllerTest extends FunctionalTestCase
{
    public static function setUpBeforeClass(): void
    {
        \define('TL_MODE', 'FE');

        parent::setUpBeforeClass();
        static::resetDatabaseSchema();
    }

    /**
     * @dataProvider provideImageConfigurations
     */
    public function testAddImageToTemplate(array $databaseFixtures, \Closure $argumentCallback, array $expectedTemplateData): void
    {
        Registry::getInstance()->reset();

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

    public function provideImageConfigurations(): \Generator
    {
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
            'singleSRC' => '../tests/Fixtures/files/public/dummy.jpg',
            'linkTitle' => '',
            'margin' => '',
            'addBefore' => true,
            'addImage' => true,
            'fullsize' => false,
        ];

        $baseRowData = [
            'singleSRC' => '../tests/Fixtures/files/public/dummy.jpg',
            'size' => [150, 100, 'crop'],
        ];

        // An image without specifying the `FilesModel` (= no meta data)
        yield 'simple image' => [
            ['image-file-with-metadata'],
            static function () use ($baseRowData) {
                return [
                    new FrontendTemplate('ce_image'),
                    $baseRowData,
                ];
            },
            $baseExpectedTemplateData,
        ];

        // An image like before but applied to a \stdClass instead of a Template
        yield 'simple image, no template object' => [
            ['image-file-with-metadata'],
            static function () use ($baseRowData) {
                return [
                    new \stdClass(),
                    $baseRowData,
                ];
            },
            $baseExpectedTemplateData,
        ];

        // An image with a `FilesModel` containing meta data for 'en' + a default page under a root page with 'language=en'
        yield 'image with meta data from tl_files' => [
            ['image-file-with-metadata', 'root-and-regular-page_en'],
            function () use ($baseRowData) {
                $this->loadGlobalObjPage(2);

                return [
                    new FrontendTemplate('ce_image'),
                    $baseRowData,
                    null,
                    null,
                    FilesModel::findById(2),
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
                    'linkTitle' => '',
                    'imageUrl' => '',
                    'caption' => 'foo caption',
                ]
            ),
        ];

        // An image with meta data like before but not available in the page's language
        yield 'image with meta data from tl_files not present in current language' => [
            ['image-file-with-metadata', 'root-and-regular-page_fr'],
            function () use ($baseRowData) {
                $this->loadGlobalObjPage(2);

                return [
                    new FrontendTemplate('ce_image'),
                    $baseRowData,
                    null,
                    null,
                    FilesModel::findById(2),
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
                    'linkTitle' => '',
                    'imageUrl' => '',
                    'caption' => '',
                ]
            ),
        ];

        // An image with meta data like before but additionally containing a link
        yield 'image with meta data from tl_files containing a link' => [
            ['image-file-with-metadata-containing-link', 'root-and-regular-page_en'],
            function () use ($baseRowData) {
                $this->loadGlobalObjPage(2);

                return [
                    new FrontendTemplate('ce_image'),
                    $baseRowData,
                    null,
                    null,
                    FilesModel::findById(2),
                ];
            },
            array_replace_recursive(
                $baseExpectedTemplateData,
                [
                    'picture' => [
                        'alt' => 'foo alt',
                    ],
                    'alt' => 'foo alt',
                    'imageTitle' => null,
                    'linkTitle' => 'foo title',
                    'imageUrl' => 'foo://bar',
                    'caption' => 'foo caption',
                    'attributes' => '',
                    'href' => 'foo://bar',
                ]
            ),
        ];

        // An image with a `FilesModel` that has no meta data and points to a non existing file
        yield 'missing image resource' => [
            ['image-file-with-missing-resource'],
            static function () use ($baseRowData) {
                return [
                    new FrontendTemplate('ce_image'),
                    $baseRowData,
                    null,
                    null,
                    FilesModel::findById(2),
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
                    'linkTitle' => '',
                    'imageUrl' => '',
                    'caption' => '',
                ]
            ),
        ];

        // An invalid image resource (singleSRC points to non existing file)
        yield 'invalid image resource' => [
            [],
            static function () use ($baseRowData) {
                return [
                    new FrontendTemplate('ce_image'),
                    array_merge($baseRowData, ['singleSRC' => 'this/does/not/exist/dummy.jpg']),
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
                ],
                'singleSRC' => 'this/does/not/exist/dummy.jpg',
                'src' => '',
                'linkTitle' => '',
                'margin' => '',
                'addImage' => true,
                'addBefore' => true,
                'fullsize' => false,
            ],
        ];

        // todo:
        //    - + insert tag in link
        //    - + custom template overwrite protection
        //    - with content element (basic)
        //    - with content element + floating/margin
        //    - with content element + fullsize/lightbox (various)
        //    - with content element + meta data overwrites
        //     ...
        //    -  bad preconditions
        //    - legacy attributes
        //     ...
    }

    private function loadGlobalObjPage(int $id): void
    {
        global $objPage;
        $objPage = PageModel::findById($id);

        $objPage->loadDetails();
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

        // Ignore generated asset paths as they differ across systems
        array_walk_recursive(
            $templateData,
            static function (&$value): void {
                if (\is_string($value) && 0 === strpos($value, 'assets/images/')) {
                    $value = 'assets/images/<anything>';
                }
            }
        );

        $this->assertSame($expected, $templateData);
    }
}
