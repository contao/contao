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
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ImageSizesTest extends TestCase
{
    private ImageSizes $imageSizes;

    private Connection&MockObject $connection;

    private EventDispatcherInterface&MockObject $eventDispatcher;

    private MockObject&Security $security;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = $this->createMock(Connection::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->security = $this->createMock(Security::class);

        $this->imageSizes = new ImageSizes(
            $this->connection,
            $this->eventDispatcher,
            $this->createStub(TranslatorInterface::class),
            $this->security,
        );
    }

    public function testReturnsAllOptionsWithImageSizes(): void
    {
        $this->expectEvent(ContaoCoreEvents::IMAGE_SIZES_ALL);
        $this->expectExampleImageSizes();
        $this->expectExamplePredefinedImageSizes();

        $this->security
            ->expects($this->never())
            ->method('isGrantedForUser')
        ;

        $options = $this->imageSizes->getAllOptions();

        $this->assertArrayHasKey('custom', $options);
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

        $this->security
            ->expects($this->never())
            ->method('isGrantedForUser')
        ;

        $options = $this->imageSizes->getAllOptions();

        $this->assertArrayHasKey('custom', $options);
        $this->assertArrayNotHasKey('My theme', $options);
    }

    public function testReturnsTheRegularUserOptions1(): void
    {
        $this->expectEvent(ContaoCoreEvents::IMAGE_SIZES_USER);
        $this->expectExampleImageSizes();

        $this->security
            ->expects($this->atLeastOnce())
            ->method('isGranted')
            ->willReturnMap([
                [ContaoCorePermissions::USER_CAN_ACCESS_IMAGE_SIZE, 42, true],
                [ContaoCorePermissions::USER_CAN_ACCESS_IMAGE_SIZE, 'crop', false],
                [ContaoCorePermissions::USER_CAN_ACCESS_IMAGE_SIZE, 'proportional', false],
                [ContaoCorePermissions::USER_CAN_ACCESS_IMAGE_SIZE, 'box', false],
            ])
        ;

        $options = $this->imageSizes->getOptionsForUser();

        $this->assertArrayNotHasKey('custom', $options);
        $this->assertArrayHasKey('My theme', $options);
        $this->assertArrayHasKey('42', $options['My theme']);
    }

    public function testReturnsTheRegularUserOptions2(): void
    {
        $this->expectEvent(ContaoCoreEvents::IMAGE_SIZES_USER);
        $this->expectExampleImageSizes();

        $this->security
            ->expects($this->atLeastOnce())
            ->method('isGranted')
            ->willReturnMap([
                [ContaoCorePermissions::USER_CAN_ACCESS_IMAGE_SIZE, 42, false],
                [ContaoCorePermissions::USER_CAN_ACCESS_IMAGE_SIZE, 'crop', false],
                [ContaoCorePermissions::USER_CAN_ACCESS_IMAGE_SIZE, 'proportional', true],
                [ContaoCorePermissions::USER_CAN_ACCESS_IMAGE_SIZE, 'box', true],
            ])
        ;

        $options = $this->imageSizes->getOptionsForUser();

        $this->assertArrayHasKey('custom', $options);
        $this->assertArrayNotHasKey('My theme', $options);
    }

    public function testReturnsTheRegularUserOptions3(): void
    {
        $this->expectEvent(ContaoCoreEvents::IMAGE_SIZES_USER);
        $this->expectExampleImageSizes();

        $this->security
            ->expects($this->atLeastOnce())
            ->method('isGranted')
            ->willReturnMap([
                [ContaoCorePermissions::USER_CAN_ACCESS_IMAGE_SIZE, 42, false],
                [ContaoCorePermissions::USER_CAN_ACCESS_IMAGE_SIZE, 'crop', false],
                [ContaoCorePermissions::USER_CAN_ACCESS_IMAGE_SIZE, 'proportional', false],
                [ContaoCorePermissions::USER_CAN_ACCESS_IMAGE_SIZE, 'box', false],
            ])
        ;

        $options = $this->imageSizes->getOptionsForUser();

        $this->assertSame([], $options);
    }

    public function testReturnsTheOptionsForASpecificUser(): void
    {
        $this->expectEvent(ContaoCoreEvents::IMAGE_SIZES_USER);
        $this->expectExampleImageSizes();

        $user = $this->createClassWithPropertiesStub(BackendUser::class);

        // Allow only one image size
        $user->imageSizes = [42];

        $this->security
            ->expects($this->atLeastOnce())
            ->method('isGrantedForUser')
            ->willReturnMap([
                [$user, ContaoCorePermissions::USER_CAN_ACCESS_IMAGE_SIZE, 42, true],
                [$user, ContaoCorePermissions::USER_CAN_ACCESS_IMAGE_SIZE, 'crop', false],
                [$user, ContaoCorePermissions::USER_CAN_ACCESS_IMAGE_SIZE, 'proportional', false],
                [$user, ContaoCorePermissions::USER_CAN_ACCESS_IMAGE_SIZE, 'box', false],
            ])
        ;

        $options = $this->imageSizes->getOptionsForUser($user);

        $this->assertArrayNotHasKey('custom', $options);
        $this->assertArrayHasKey('My theme', $options);
        $this->assertArrayHasKey('42', $options['My theme']);
    }

    public function testServiceIsResetable(): void
    {
        $this->eventDispatcher
            ->expects($this->exactly(3))
            ->method('dispatch')
        ;

        $this->connection
            ->expects($this->exactly(2))
            ->method('fetchAllAssociative')
            ->willReturn([])
        ;

        $this->security
            ->expects($this->never())
            ->method('isGrantedForUser')
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
