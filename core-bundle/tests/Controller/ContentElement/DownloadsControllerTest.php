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
use Contao\CoreBundle\Image\Preview\PreviewFactory;
use Contao\StringUtil;
use Symfony\Component\Security\Core\Security;

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
                'inline' => '',
            ],
        );

        $expectedOutput = <<<'HTML'
            <div class="content_element/download">
                <a href="https://example.com/files/image1.jpg" title="translated(contao_default:MSC.download[image1 title])" type="image/jpg">image1.jpg</a>
            </div>
            HTML;

        $this->assertSameHtml($expectedOutput, $response->getContent());
    }

    public function testOutputsSingleDownloadWithCustomMetadata(): void
    {
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
                'inline' => '',
            ],
        );

        $expectedOutput = <<<'HTML'
            <div class="content_element/download">
                <a href="https://example.com/files/image1.jpg" title="translated(contao_default:MSC.download[Download the file])" type="image/jpg">The file</a>
            </div>
            HTML;

        $this->assertSameHtml($expectedOutput, $response->getContent());
    }

    public function testFiltersFileExtensions(): void
    {
        $response = $this->renderWithModelData(
            $this->getDownloadsController(),
            [
                'type' => 'download',
                'singleSRC' => StringUtil::uuidToBin(ContentElementTestCase::FILE_VIDEO_MP4),
                'sortBy' => '',
            ],
        );

        $expectedOutput = <<<'HTML'
            <div class="content_element/download">
            </div>
            HTML;

        $this->assertSameHtml($expectedOutput, $response->getContent());
    }

    public function testOutputsDownloadsList(): void
    {
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
            ],
        );

        $expectedOutput = <<<'HTML'
            <div class="content_element/downloads">
                <ul>
                    <li>
                        <a href="https://example.com/files/image1.jpg" title="translated(contao_default:MSC.download[image1 title])" type="image/jpg">image1.jpg</a>
                    </li>
                    <li>
                        <a href="https://example.com/files/image2.jpg" title="translated(contao_default:MSC.download[image2.jpg])">image2.jpg</a>
                    </li>
                </ul>
            </div>
            HTML;

        $this->assertSameHtml($expectedOutput, $response->getContent());
    }

    private function getDownloadsController(): DownloadsController
    {
        $security = $this->createMock(Security::class);
        $fileDownloadHelper = $this->createMock(FileDownloadHelper::class);

        return new DownloadsController(
            $security,
            $this->getDefaultStorage(),
            $fileDownloadHelper,
            $this->createMock(PreviewFactory::class),
            $this->getDefaultStudio(),
            'project/dir',
            ['jpg', 'txt']
        );
    }
}
