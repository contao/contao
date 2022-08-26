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
use Contao\CoreBundle\Event\ImageSizesEvent;
use Contao\CoreBundle\Image\ImageSizes;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Service\ResetInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ImageSizesTest extends TestCase
{
    private ImageSizes $imageSizes;

    /**
     * @var Connection&MockObject
     */
    private Connection $connection;

    /**
     * @var EventDispatcherInterface&MockObject
     */
    private EventDispatcherInterface $eventDispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['TL_CROP'] = [
            'image_sizes' => [],
            'relative' => [
                'proportional', 'box',
            ],
            'exact' => [
                'crop',
                'left_top', 'center_top', 'right_top',
                'left_center', 'center_center', 'right_center',
                'left_bottom', 'center_bottom', 'right_bottom',
            ],
        ];

        $this->connection = $this->createMock(Connection::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->imageSizes = new ImageSizes(
            $this->connection,
            $this->eventDispatcher,
            $this->mockContaoFramework(),
            $this->createMock(TranslatorInterface::class)
        );
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_CROP']);

        parent::tearDown();
    }

    public function testReturnsAllOptionsWithImageSizes(): void
    {
        $this->expectEvent(ContaoCoreEvents::IMAGE_SIZES_ALL);
        $this->expectExampleImageSizes();
        $this->expectExamplePredefinedImageSizes();

        $options = $this->imageSizes->getAllOptions();

        $this->assertArrayHasKey('relative', $options);
        $this->assertArrayHasKey('exact', $options);
        $this->assertArrayHasKey('My theme', $options);
        $this->assertArrayHasKey('42', $options['My theme']);
        $this->assertArrayHasKey('image_sizes', $options);
        $this->assertArrayHasKey('_foo', $options['image_sizes']);
        $this->assertArrayHasKey('_bar', $options['image_sizes']);
    }

    public function testReturnsAllOptionsWithoutImageSizes(): void
    {
        $this->expectEvent(ContaoCoreEvents::IMAGE_SIZES_ALL);
        $this->expectImageSizes([]);

        $options = $this->imageSizes->getAllOptions();

        $this->assertArrayHasKey('relative', $options);
        $this->assertArrayHasKey('exact', $options);
        $this->assertArrayNotHasKey('My theme', $options);
    }

    public function testReturnsTheAdminUserOptions(): void
    {
        $this->expectEvent(ContaoCoreEvents::IMAGE_SIZES_USER);
        $this->expectExampleImageSizes();

        $user = $this->mockClassWithProperties(BackendUser::class);
        $user->isAdmin = true;

        $options = $this->imageSizes->getOptionsForUser($user);

        // TL_CROP would not be returned without the admin check, because it is
        // not within the allowed image sizes
        $this->assertArrayHasKey('relative', $options);
    }

    public function testReturnsTheRegularUserOptions(): void
    {
        $this->expectEvent(ContaoCoreEvents::IMAGE_SIZES_USER);
        $this->expectExampleImageSizes();

        $user = $this->mockClassWithProperties(BackendUser::class);
        $user->isAdmin = false;

        // Allow only one image size
        $user->imageSizes = [42];

        $options = $this->imageSizes->getOptionsForUser($user);

        $this->assertArrayNotHasKey('relative', $options);
        $this->assertArrayNotHasKey('exact', $options);
        $this->assertArrayHasKey('My theme', $options);
        $this->assertArrayHasKey('42', $options['My theme']);

        $user = $this->mockClassWithProperties(BackendUser::class);
        $user->isAdmin = false;

        // Allow only some TL_CROP options
        $user->imageSizes = ['proportional', 'box'];

        $options = $this->imageSizes->getOptionsForUser($user);

        $this->assertArrayHasKey('relative', $options);
        $this->assertArrayNotHasKey('exact', $options);
        $this->assertArrayNotHasKey('My theme', $options);

        $user = $this->mockClassWithProperties(BackendUser::class);
        $user->isAdmin = false;

        // Allow nothing
        $user->imageSizes = [];

        $options = $this->imageSizes->getOptionsForUser($user);

        $this->assertSame([], $options);
    }

    public function testServiceIsResetable(): void
    {
        $this->assertInstanceOf(ResetInterface::class, $this->imageSizes);

        $this->eventDispatcher
            ->expects($this->exactly(3))
            ->method('dispatch')
        ;

        $this->connection
            ->expects($this->exactly(2))
            ->method('fetchAllAssociative')
            ->willReturn([])
        ;

        // Test that fetchAllAssociative() is only called once
        $this->imageSizes->getAllOptions();
        $this->imageSizes->getAllOptions();

        $this->imageSizes->reset();
        $this->imageSizes->getAllOptions();
    }

    /**
     * Adds an expected method call to the event dispatcher mock object.
     */
    private function expectEvent(string $event): void
    {
        $this->eventDispatcher
            ->expects($this->atLeastOnce())
            ->method('dispatch')
            ->with($this->isInstanceOf(ImageSizesEvent::class), $event)
        ;
    }

    /**
     * Adds an expected method call to the database connection mock object.
     */
    private function expectImageSizes(array $imageSizes): void
    {
        $this->connection
            ->expects($this->atLeastOnce())
            ->method('fetchAllAssociative')
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
                'theme' => 'My theme',
            ],
        ]);
    }

    private function expectExamplePredefinedImageSizes(): void
    {
        $this->imageSizes->setPredefinedSizes([
            '_foo' => ['width' => 123, 'height' => 456],
            '_bar' => ['width' => 123, 'height' => 456],
        ]);
    }
}
