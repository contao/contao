<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Image;

use Contao\BackendUser;
use Contao\CoreBundle\Event\ContaoCoreEvents;
use Contao\CoreBundle\Image\ImageSizes;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ImageSizesTest extends TestCase
{
    /**
     * @var Connection|MockObject
     */
    private $connection;

    /**
     * @var EventDispatcherInterface|MockObject
     */
    private $eventDispatcher;

    /**
     * @var ImageSizes
     */
    private $imageSizes;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $GLOBALS['TL_CROP'] = [
            'relative' => [
                'proportional', 'box',
            ],
            'exact' => [
                'crop',
                'left_top',    'center_top',    'right_top',
                'left_center', 'center_center', 'right_center',
                'left_bottom', 'center_bottom', 'right_bottom',
            ],
        ];

        $this->connection = $this->createMock(Connection::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->imageSizes = new ImageSizes($this->connection, $this->eventDispatcher, $this->mockContaoFramework());
    }

    public function testReturnsAllOptionsWithImageSizes(): void
    {
        $this->expectEvent(ContaoCoreEvents::IMAGE_SIZES_ALL);
        $this->expectExampleImageSizes();

        $options = $this->imageSizes->getAllOptions();

        $this->assertArraySubset($GLOBALS['TL_CROP'], $options);
        $this->assertArrayHasKey('image_sizes', $options);
        $this->assertArrayHasKey('42', $options['image_sizes']);
    }

    public function testReturnsAllOptionsWithoutImageSizes(): void
    {
        $this->expectEvent(ContaoCoreEvents::IMAGE_SIZES_ALL);
        $this->expectImageSizes([]);

        $options = $this->imageSizes->getAllOptions();

        $this->assertArraySubset($GLOBALS['TL_CROP'], $options);
        $this->assertArrayNotHasKey('image_sizes', $options);
    }

    public function testReturnsTheAdminUserOptions(): void
    {
        $this->expectEvent(ContaoCoreEvents::IMAGE_SIZES_USER);
        $this->expectExampleImageSizes();

        $properties = [
            'imageSizes' => ['image_sizes' => '42'],
            'isAdmin' => true,
        ];

        /** @var BackendUser|MockObject $user */
        $user = $this->mockClassWithProperties(BackendUser::class, $properties);
        $options = $this->imageSizes->getOptionsForUser($user);

        // TL_CROP would not be returned if the admin check was not done (because it's not in the allowed imageSizes)
        $this->assertArraySubset($GLOBALS['TL_CROP'], $options);
    }

    public function testReturnsTheRegularUserOptions(): void
    {
        $this->expectEvent(ContaoCoreEvents::IMAGE_SIZES_USER);
        $this->expectExampleImageSizes();

        // Allow only one image size
        $properties = [
            'imageSizes' => [42],
            'isAdmin' => false,
        ];

        /** @var BackendUser|MockObject $user */
        $user = $this->mockClassWithProperties(BackendUser::class, $properties);
        $options = $this->imageSizes->getOptionsForUser($user);

        $this->assertArrayNotHasKey('relative', $options);
        $this->assertArrayNotHasKey('exact', $options);
        $this->assertArrayHasKey('image_sizes', $options);
        $this->assertArrayHasKey('42', $options['image_sizes']);

        // Allow only some TL_CROP options
        $properties = [
            'imageSizes' => ['proportional', 'box'],
            'isAdmin' => false,
        ];

        /** @var BackendUser|MockObject $user */
        $user = $this->mockClassWithProperties(BackendUser::class, $properties);
        $options = $this->imageSizes->getOptionsForUser($user);

        $this->assertArrayHasKey('relative', $options);
        $this->assertArrayNotHasKey('exact', $options);
        $this->assertArrayNotHasKey('image_sizes', $options);

        // Allow nothing
        $properties = [
            'imageSizes' => [],
            'isAdmin' => false,
        ];

        /** @var BackendUser|MockObject $user */
        $user = $this->mockClassWithProperties(BackendUser::class, $properties);
        $options = $this->imageSizes->getOptionsForUser($user);

        $this->assertSame([], $options);
    }

    /**
     * Adds an expected method call to the event dispatcher mock object.
     */
    private function expectEvent(string $event): void
    {
        $this->eventDispatcher
            ->expects($this->atLeastOnce())
            ->method('dispatch')
            ->with($event, $this->isInstanceOf('Contao\CoreBundle\Event\ImageSizesEvent'))
        ;
    }

    /**
     * Adds an expected method call to the database connection mock object.
     */
    private function expectImageSizes(array $imageSizes): void
    {
        $this->connection
            ->expects($this->atLeastOnce())
            ->method('fetchAll')
            ->willReturn($imageSizes)
        ;
    }

    /**
     * Adds expected example image sizes to the database connection mock object.
     */
    private function expectExampleImageSizes(): void
    {
        $this->expectImageSizes([
            [
                'id' => '42',
                'name' => 'foobar',
                'width' => '',
                'height' => '',
            ],
        ]);
    }
}
