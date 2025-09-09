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

use Contao\CoreBundle\Controller\ContentElement\ImagesController;
use Contao\CoreBundle\Filesystem\FilesystemItem;
use Contao\CoreBundle\Image\Studio\Figure;
use Contao\CoreBundle\Pagination\Pagination;
use Contao\CoreBundle\Pagination\PaginationFactoryInterface;
use Contao\CoreBundle\Tests\Image\Studio\ImageResultStub;
use Contao\StringUtil;
use Symfony\Bundle\SecurityBundle\Security;

class ImagesControllerTest extends ContentElementTestCase
{
    public function testOutputsSingleImage(): void
    {
        $security = $this->createMock(Security::class);

        $paginationFactory = $this->createMock(PaginationFactoryInterface::class);
        $paginationFactory
            ->expects($this->never())
            ->method('create')
        ;

        $response = $this->renderWithModelData(
            new ImagesController($security, $this->getDefaultStorage(), $this->getDefaultStudio(), $paginationFactory, ['jpg']),
            [
                'type' => 'image',
                'singleSRC' => StringUtil::uuidToBin(ContentElementTestCase::FILE_IMAGE1),
                'sortBy' => '',
                'numberOfItems' => '0',
                'size' => '',
                'fullsize' => true,
                'perPage' => '4',
                'perRow' => '2',
            ],
        );

        $expectedOutput = <<<'HTML'
            <div class="content-image">
                <figure>
                    <img src="files/image1.jpg" alt>
                </figure>
            </div>
            HTML;

        $this->assertSameHtml($expectedOutput, $response->getContent());
    }

    public function testOutputsGallery(): void
    {
        $security = $this->createMock(Security::class);

        $paginationFactory = $this->createMock(PaginationFactoryInterface::class);
        $paginationFactory
            ->expects($this->never())
            ->method('create')
        ;

        $response = $this->renderWithModelData(
            new ImagesController($security, $this->getDefaultStorage(), $this->getDefaultStudio(), $paginationFactory, ['jpg']),
            [
                'type' => 'gallery',
                'multiSRC' => serialize([
                    StringUtil::uuidToBin(ContentElementTestCase::FILE_IMAGE1),
                    StringUtil::uuidToBin(ContentElementTestCase::FILE_IMAGE2),
                    StringUtil::uuidToBin(ContentElementTestCase::FILE_IMAGE3),
                ]),
                'sortBy' => 'name_desc',
                'numberOfItems' => 2,
                'size' => '',
                'fullsize' => true,
                'perPage' => 4,
                'perRow' => 2,
            ],
        );

        $expectedOutput = <<<'HTML'
            <div class="content-gallery content-gallery--cols-2">
                <ul>
                    <li>
                        <figure>
                            <img src="files/image3.jpg" alt>
                        </figure>
                    </li>
                    <li>
                        <figure>
                            <img src="files/image2.jpg" alt>
                        </figure>
                    </li>
                </ul>
            </div>
            HTML;

        $this->assertSameHtml($expectedOutput, $response->getContent());
    }

    public function testIgnoresInvalidTypes(): void
    {
        $security = $this->createMock(Security::class);

        $paginationFactory = $this->createMock(PaginationFactoryInterface::class);
        $paginationFactory
            ->expects($this->never())
            ->method('create')
        ;

        $response = $this->renderWithModelData(
            new ImagesController($security, $this->getDefaultStorage(), $this->getDefaultStudio(), $paginationFactory, ['svg', 'jpg', 'png']),
            [
                'type' => 'gallery',
                'multiSRC' => serialize([
                    StringUtil::uuidToBin(ContentElementTestCase::FILE_IMAGE1),
                    StringUtil::uuidToBin(ContentElementTestCase::FILE_VIDEO_MP4),
                ]),
                'sortBy' => 'name_desc',
                'numberOfItems' => 0,
                'size' => '',
                'fullsize' => true,
                'perPage' => 1,
                'perRow' => 1,
            ],
        );

        $expectedOutput = <<<'HTML'
            <div class="content-gallery content-gallery--cols-1">
                <ul>
                    <li>
                        <figure>
                            <img src="files/image1.jpg" alt>
                        </figure>
                    </li>
                </ul>
            </div>
            HTML;

        $this->assertSameHtml($expectedOutput, $response->getContent());
    }

    public function testDoesNotOutputAnythingWithoutImages(): void
    {
        $security = $this->createMock(Security::class);

        $paginationFactory = $this->createMock(PaginationFactoryInterface::class);
        $paginationFactory
            ->expects($this->never())
            ->method('create')
        ;

        $response = $this->renderWithModelData(
            new ImagesController($security, $this->getDefaultStorage(), $this->getDefaultStudio(), $paginationFactory, ['jpg']),
            [
                'type' => 'image',
                'singleSRC' => null,
                'sortBy' => 'name_desc',
                'fullsize' => true,
            ],
        );

        $this->assertSame('', $response->getContent());

        $response = $this->renderWithModelData(
            new ImagesController($security, $this->getDefaultStorage(), $this->getDefaultStudio(), $paginationFactory, ['jpg']),
            [
                'type' => 'gallery',
                'multiSRC' => null,
                'sortBy' => 'name_desc',
                'fullsize' => true,
            ],
        );

        $this->assertSame('', $response->getContent());
    }

    public function testIgnoresMissingImages(): void
    {
        $security = $this->createMock(Security::class);

        $paginationFactory = $this->createMock(PaginationFactoryInterface::class);
        $paginationFactory
            ->expects($this->never())
            ->method('create')
        ;

        $response = $this->renderWithModelData(
            new ImagesController($security, $this->getDefaultStorage(), $this->getDefaultStudio(), $paginationFactory, ['svg', 'jpg', 'png']),
            [
                'type' => 'gallery',
                'multiSRC' => serialize([
                    StringUtil::uuidToBin(ContentElementTestCase::FILE_IMAGE1),
                    StringUtil::uuidToBin(ContentElementTestCase::FILE_IMAGE_MISSING),
                ]),
                'sortBy' => 'name_desc',
                'numberOfItems' => 0,
                'size' => '',
                'fullsize' => true,
                'perPage' => 1,
                'perRow' => 1,
            ],
        );

        $expectedOutput = <<<'HTML'
            <div class="content-gallery content-gallery--cols-1">
                <ul>
                    <li>
                        <figure>
                            <img src="files/image1.jpg" alt>
                        </figure>
                    </li>
                </ul>
            </div>
            HTML;

        $this->assertSameHtml($expectedOutput, $response->getContent());
    }

    public function testOutputsPagination(): void
    {
        $security = $this->createMock(Security::class);

        $items = [
            new FilesystemItem(true, 'image1.jpg', null, null, 'image/jpeg'),
            new FilesystemItem(true, 'image2.jpg', null, null, 'image/jpeg'),
        ];

        $pagination = $this->createMock(Pagination::class);
        $pagination
            ->expects($this->once())
            ->method('getPages')
            ->willReturn([1, 2])
        ;

        $pagination
            ->method('getCurrent')
            ->willReturn(1)
        ;

        $pagination
            ->expects($this->exactly(2))
            ->method('getUrlForPage')
            ->willReturnCallback(static fn (int $page): string => '/foobar?page='.$page)
        ;

        $items = [
            new Figure(new ImageResultStub(['src' => 'files/image3.jpg'])),
            new Figure(new ImageResultStub(['src' => 'files/image2.jpg'])),
        ];

        $pagination
            ->expects($this->once())
            ->method('getItemsForPage')
            ->willReturn($items)
        ;

        $paginationFactory = $this->createMock(PaginationFactoryInterface::class);
        $paginationFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($pagination)
        ;

        $response = $this->renderWithModelData(
            new ImagesController($security, $this->getDefaultStorage(), $this->getDefaultStudio(), $paginationFactory, ['jpg']),
            [
                'type' => 'gallery',
                'multiSRC' => serialize([
                    StringUtil::uuidToBin(ContentElementTestCase::FILE_IMAGE1),
                    StringUtil::uuidToBin(ContentElementTestCase::FILE_IMAGE2),
                    StringUtil::uuidToBin(ContentElementTestCase::FILE_IMAGE3),
                ]),
                'sortBy' => 'name_desc',
                'numberOfItems' => 0,
                'size' => '',
                'fullsize' => true,
                'perPage' => 2,
                'perRow' => 2,
                'serverPagination' => true,
            ],
        );

        $expectedOutput = <<<'HTML'
            <div class="content-gallery content-gallery--cols-2">
                <ul>
                    <li>
                        <figure>
                            <img src="files/image3.jpg" alt>
                        </figure>
                    </li>
                    <li>
                        <figure>
                            <img src="files/image2.jpg" alt>
                        </figure>
                    </li>
                </ul>
                <!-- indexer::stop -->
                <nav class="pagination" role="navigation" aria-label="translated(contao_default:MSC.pagination)">
                    <p>translated(contao_default:MSC.totalPages[1, 0])</p>
                    <ul>
                        <li>
                            <a href="/foobar?page=1" aria-label="translated(contao_default:MSC.goToPage[1])" aria-current="page" class="link active">1</a>
                        </li>
                        <li>
                            <a href="/foobar?page=2" aria-label="translated(contao_default:MSC.goToPage[2])" class="link">2</a>
                        </li>
                    </ul>
                </nav>
                <!-- indexer::continue -->
            </div>
            HTML;

        $this->assertSameHtml($expectedOutput, $response->getContent());
    }
}
