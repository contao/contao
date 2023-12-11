<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Controller\ContentElement;

use Contao\CoreBundle\Controller\ContentElement\DownloadsController;
use Contao\CoreBundle\Filesystem\FileDownloadHelper;
use Contao\StringUtil;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DownloadsControllerTest extends ContentElementTestCase
{
    public function testOutputsSingleDownload(): void
    {
        $response = $this->renderWithModelData(
            $this->getDownloadsController(),
            [
                'type' => 'download',
                'singleSRC' => StringUtil::uuidToBin(ContentElementTestCase::FILE_IMAGE1),
                'sortBy' => '',
                'numberOfItems' => '0',
                'showPreview' => '',
                'overwriteLink' => '',
                'inline' => false,
                'fullsize' => false,
            ],
            null,
            false,
            $responseContext,
            $this->getAdjustedContainer(),
        );

        $expectedOutput = <<<'HTML'
            <div class="download-element ext-jpg content-download">
                <a href="https://example.com/files/image1.jpg" title="translated(contao_default:MSC.download[image1 title])" type="image/jpg">image1 title</a>
            </div>
            HTML;

        $this->assertSameHtml($expectedOutput, $response->getContent());
    }

    public function testOutputsSingleDownloadWithCustomMetadata(): void
    {
<<<<<<< HEAD
        $response = $this->renderWithModelData($this->getDownloadsController(), [
            'type' => 'download',
            'singleSRC' => StringUtil::uuidToBin(ContentElementTestCase::FILE_IMAGE1),
            'sortBy' => '',
            'numberOfItems' => '0',
            'showPreview' => '',
            'overwriteLink' => '1',
            'linkTitle' => 'Download the file',
            'titleText' => 'The file',
            'inline' => false,
        ]);
=======
        $response = $this->renderWithModelData(
            $this->getDownloadsController(),
            [
                'type' => 'download',
                'singleSRC' => StringUtil::uuidToBin(ContentElementTestCase::FILE_IMAGE1),
                'sortBy' => '',
                'numberOfItems' => '0',
                'showPreview' => '',
                'overwriteLink' => '1',
                'linkTitle' => 'Download the file',
                'titleText' => 'The file',
                'inline' => false,
                'fullsize' => false,
            ],
            null,
            false,
            $responseContext,
            $this->getAdjustedContainer(),
        );
>>>>>>> origin/5.x

        $expectedOutput = <<<'HTML'
            <div class="download-element ext-jpg content-download">
                <a href="https://example.com/files/image1.jpg" title="translated(contao_default:MSC.download[Download the file])" type="image/jpg">The file</a>
            </div>
            HTML;

        $this->assertSameHtml($expectedOutput, $response->getContent());
    }

    public function testFiltersFileExtensions(): void
    {
<<<<<<< HEAD
        $response = $this->renderWithModelData($this->getDownloadsController(), [
            'type' => 'download',
            'singleSRC' => StringUtil::uuidToBin(ContentElementTestCase::FILE_VIDEO_MP4),
            'sortBy' => '',
        ]);
=======
        $response = $this->renderWithModelData(
            $this->getDownloadsController(),
            [
                'type' => 'download',
                'singleSRC' => StringUtil::uuidToBin(ContentElementTestCase::FILE_VIDEO_MP4),
                'sortBy' => '',
                'fullsize' => false,
            ],
            null,
            false,
            $responseContext,
            $this->getAdjustedContainer(),
        );
>>>>>>> origin/5.x

        $expectedOutput = <<<'HTML'
            <div class="content-download">
            </div>
            HTML;

        $this->assertSameHtml($expectedOutput, $response->getContent());
    }

    public function testOutputsDownloadsList(): void
    {
<<<<<<< HEAD
        $response = $this->renderWithModelData($this->getDownloadsController(), [
            'type' => 'downloads',
            'multiSRC' => serialize([
                StringUtil::uuidToBin(ContentElementTestCase::FILE_IMAGE1),
                StringUtil::uuidToBin(ContentElementTestCase::FILE_VIDEO_MP4),
                StringUtil::uuidToBin(ContentElementTestCase::FILE_IMAGE2),
            ]),
            'sortBy' => '',
            'numberOfItems' => 2,
            'showPreview' => '',
            'inline' => false,
        ]);
=======
        $response = $this->renderWithModelData(
            $this->getDownloadsController(),
            [
                'type' => 'downloads',
                'multiSRC' => serialize([
                    StringUtil::uuidToBin(ContentElementTestCase::FILE_IMAGE1),
                    StringUtil::uuidToBin(ContentElementTestCase::FILE_VIDEO_MP4),
                    StringUtil::uuidToBin(ContentElementTestCase::FILE_IMAGE2),
                ]),
                'sortBy' => '',
                'numberOfItems' => 2,
                'showPreview' => '',
                'inline' => false,
                'fullsize' => false,
            ],
            null,
            false,
            $responseContext,
            $this->getAdjustedContainer(),
        );
>>>>>>> origin/5.x

        $expectedOutput = <<<'HTML'
            <div class="content-downloads">
                <ul>
                    <li class="download-element ext-jpg">
                        <a href="https://example.com/files/image1.jpg" title="translated(contao_default:MSC.download[image1 title])" type="image/jpg">image1 title</a>
                    </li>
                    <li class="download-element ext-jpg">
                        <a href="https://example.com/files/image2.jpg" title="translated(contao_default:MSC.download[image2.jpg])" type="image/jpeg">image2.jpg</a>
                    </li>
                </ul>
            </div>
            HTML;

        $this->assertSameHtml($expectedOutput, $response->getContent());
    }

    private function getDownloadsController(): DownloadsController
    {
        $security = $this->createMock(Security::class);

        return new DownloadsController(
            $security,
            $this->getDefaultStorage(),
        );
    }

    private function getAdjustedContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->set('contao.filesystem.file_download_helper', $this->createMock(FileDownloadHelper::class));
        $container->setParameter('contao.downloadable_files', ['jpg', 'txt']);

        return $container;
    }
}
