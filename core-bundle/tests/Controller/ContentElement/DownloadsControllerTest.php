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

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\DownloadsController;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\CoreBundle\Filesystem\FileDownloadHelper;
use Contao\StringUtil;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
            <div class="content-download download-element ext-jpg">
                <a href="https://example.com/files/image1.jpg" type="image/jpeg">image1 title</a>
            </div>
            HTML;

        $this->assertSameHtml($expectedOutput, $response->getContent());
    }

    public function testOutputsSingleDownloadWithNoMetadata(): void
    {
        $response = $this->renderWithModelData(
            $this->getDownloadsController(),
            [
                'type' => 'download',
                'singleSRC' => StringUtil::uuidToBin(ContentElementTestCase::FILE_IMAGE2),
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
            <div class="content-download download-element ext-jpg">
                <a href="https://example.com/files/image2.jpg" type="image/jpeg">image2.jpg</a>
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
                'linkTitle' => 'The file',
                'titleText' => 'Download the file',
                'inline' => false,
                'fullsize' => false,
            ],
            null,
            false,
            $responseContext,
            $this->getAdjustedContainer(),
        );

        $expectedOutput = <<<'HTML'
            <div class="content-download download-element ext-jpg">
                <a href="https://example.com/files/image1.jpg" title="Download the file" type="image/jpeg">The file</a>
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
                'fullsize' => false,
            ],
            null,
            false,
            $responseContext,
            $this->getAdjustedContainer(),
        );

        $expectedOutput = <<<'HTML'
            <div class="content-download">
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
                'inline' => false,
                'fullsize' => false,
            ],
            null,
            false,
            $responseContext,
            $this->getAdjustedContainer(),
        );

        $expectedOutput = <<<'HTML'
            <div class="content-downloads">
                <ul>
                    <li class="download-element ext-jpg">
                        <a href="https://example.com/files/image1.jpg" type="image/jpeg">image1 title</a>
                    </li>
                    <li class="download-element ext-jpg">
                        <a href="https://example.com/files/image2.jpg" type="image/jpeg">image2.jpg</a>
                    </li>
                </ul>
            </div>
            HTML;

        $this->assertSameHtml($expectedOutput, $response->getContent());
    }

    public function testDoesNotHandleNonDownloadRequests(): void
    {
        $fileDownloadHelper = $this->mockFileDownloadHelper(false);
        $fileDownloadHelper
            ->expects($this->never())
            ->method('handle')
        ;

        $this->handleDownload($fileDownloadHelper, Request::create('https://example.com/?_hash=foo'));
    }

    public function testIgnoresDownloadsOfOtherElements(): void
    {
        $fileDownloadHelper = $this->mockFileDownloadHelper(true);
        $fileDownloadHelper
            ->expects($this->once())
            ->method('handle')
            ->willReturn(new Response('', Response::HTTP_NO_CONTENT))
        ;

        $this->handleDownload($fileDownloadHelper, Request::create('https://example.com/?p=image1.jpg&_hash=foo'));
    }

    #[DataProvider('provideDownloadResponses')]
    public function testThrowsResponsesOfDownloadRequests(Response $response): void
    {
        $fileDownloadHelper = $this->mockFileDownloadHelper(true);
        $fileDownloadHelper
            ->expects($this->once())
            ->method('handle')
            ->willReturn($response)
        ;

        try {
            $this->handleDownload($fileDownloadHelper, Request::create('https://example.com/?p=image1.jpg&_hash=foo'));
        } catch (ResponseException $exception) {
            $this->assertSame($response, $exception->getResponse());

            return;
        }

        $this->fail('Expected a ResponseException to be thrown.');
    }

    public static function provideDownloadResponses(): iterable
    {
        yield 'streamed file' => [new StreamedResponse()];
        yield 'invalid signature' => [new Response('', Response::HTTP_FORBIDDEN)];
        yield 'missing file' => [new Response('', Response::HTTP_NOT_FOUND)];
        yield 'file no longer accessible' => [new Response('', Response::HTTP_GONE)];
    }

    private function mockFileDownloadHelper(bool $isDownloadRequest): FileDownloadHelper&MockObject
    {
        $fileDownloadHelper = $this->createMock(FileDownloadHelper::class);
        $fileDownloadHelper
            ->method('isDownloadRequest')
            ->willReturn($isDownloadRequest)
        ;

        return $fileDownloadHelper;
    }

    private function handleDownload(FileDownloadHelper $fileDownloadHelper, Request $request): void
    {
        $container = new ContainerBuilder();
        $container->set('contao.filesystem.file_download_helper', $fileDownloadHelper);

        $controller = $this->getDownloadsController();
        $controller->setContainer($container);

        $model = $this->createClassWithPropertiesStub(ContentModel::class, ['id' => 42]);

        (new \ReflectionMethod($controller, 'handleDownload'))->invoke($controller, $request, $model);
    }

    private function getDownloadsController(): DownloadsController
    {
        return new DownloadsController(
            $this->createStub(Security::class),
            $this->getDefaultStorage(),
        );
    }

    private function getAdjustedContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->set('contao.filesystem.file_download_helper', $this->createStub(FileDownloadHelper::class));

        return $container;
    }
}
