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

use Closure;
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
    public function testAddImageToTemplate(array $databaseFixtures, Closure $argumentCallback, array $expectedTemplateData): void
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
        $getBaseTemplateData = static function (array $originalDimensions, array $targetDimensions, string $sourcePath, string $assetPath) {
            return [
                'width' => $originalDimensions[0],
                'height' => $originalDimensions[1],
                'imgSize' => sprintf(' width="%d" height="%d"', ...$targetDimensions),
                'arrSize' => [
                    0 => $targetDimensions[0],
                    1 => $targetDimensions[1],
                    2 => 2,
                    3 => sprintf('width="%d" height="%d"', ...$targetDimensions),
                    'bits' => 8,
                    'channels' => 3,
                    'mime' => 'image/jpeg',
                ],
                'picture' => [
                    'img' => [
                        'width' => $targetDimensions[0],
                        'height' => $targetDimensions[1],
                        'hasSingleAspectRatio' => true,
                        'src' => $assetPath,
                        'srcset' => $assetPath,
                    ],
                    'sources' => [],
                    'alt' => '',
                ],
                'src' => $assetPath,
                'singleSRC' => $sourcePath,
                'linkTitle' => '',
                'margin' => '',
                'addBefore' => true,
                'addImage' => true,
                'fullsize' => false,
            ];
        };

        // An image from tl_files but without specifying the `FilesModel` (= no meta data)
        yield 'simple image' => [
            ['image-file-with-metadata'],
            static function () {
                return [
                    new FrontendTemplate('ce_image'),
                    [
                        'singleSRC' => '../tests/Fixtures/files/public/dummy.jpg',
                        'size' => [150, 100, 'crop'],
                    ],
                ];
            },
            $getBaseTemplateData([200, 200], [150, 100], '../tests/Fixtures/files/public/dummy.jpg', 'assets/images/3/dummy-f134771a.jpg'),
        ];

        // An image from tl_files containing meta data for 'en' and a default page with a root page with 'language=en'
        yield 'image with meta data from tl_files' => [
            ['image-file-with-metadata', 'root-with-language-and-page'],
            function () {
                $this->loadGlobalObjPage(2);

                return [
                    new FrontendTemplate('ce_image'),
                    [
                        'singleSRC' => '../tests/Fixtures/files/public/dummy.jpg',
                        'size' => [150, 100, 'crop'],
                    ],
                    null,
                    null,
                    FilesModel::findById(2),
                ];
            },
            array_replace_recursive(
                $getBaseTemplateData([200, 200], [150, 100], '../tests/Fixtures/files/public/dummy.jpg', 'assets/images/3/dummy-f134771a.jpg'),
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

        // Meta data like before but additionally containing a link
        yield 'image with meta data from tl_files containing a link' => [
            ['image-file-with-metadata-containing-link', 'root-with-language-and-page'],
            function () {
                $this->loadGlobalObjPage(2);

                return [
                    new FrontendTemplate('ce_image'),
                    [
                        'singleSRC' => '../tests/Fixtures/files/public/dummy.jpg',
                        'size' => [150, 100, 'crop'],
                    ],
                    null,
                    null,
                    FilesModel::findById(2),
                ];
            },
            array_replace_recursive(
                $getBaseTemplateData([200, 200], [150, 100], '../tests/Fixtures/files/public/dummy.jpg', 'assets/images/3/dummy-f134771a.jpg'),
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

        // todo:
        //    - + empty meta data
        //    - + insert tag in link
        //    - with content element (basic)
        //    - with content element + floating/margin
        //    - with content element + fullsize/lightbox (various)
        //    - with content element + meta data overwrites
        //     ...
        //    - no file / bad preconditions
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

        $this->assertSame($expected, $templateData);
    }
}
