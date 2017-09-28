<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Image;

use Contao\BackendUser;
use Contao\CoreBundle\Event\ContaoCoreEvents;
use Contao\CoreBundle\Image\ImageSizes;
use Contao\CoreBundle\Tests\TestCase;
use Contao\System;
use Doctrine\DBAL\Connection;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Tests the ImageSizes class.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class ImageSizesTest extends TestCase
{
    /**
     * @var Connection|\PHPUnit_Framework_MockObject_MockObject
     */
    private $connection;

    /**
     * @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $eventDispatcher;

    /**
     * @var ImageSizes
     */
    private $imageSizes;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $framework = $this->mockContaoFramework();
        $framework->initialize();

        System::setContainer($this->mockContainerWithContaoScopes());

        require_once __DIR__.'/../../src/Resources/contao/config/config.php';

        $this->connection = $this->createMock(Connection::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->imageSizes = new ImageSizes($this->connection, $this->eventDispatcher, $framework);
    }

    /**
     * Tests the object instantiation.
     */
    public function testCanBeInstantiated()
    {
        $this->assertInstanceOf('Contao\CoreBundle\Image\ImageSizes', $this->imageSizes);
    }

    /**
     * Tests getting all options with image sizes.
     */
    public function testReturnsAllOptionsWithImageSizes()
    {
        $this->expectEvent(ContaoCoreEvents::IMAGE_SIZES_ALL);
        $this->expectExampleImageSizes();

        $options = $this->imageSizes->getAllOptions();

        $this->assertArraySubset($GLOBALS['TL_CROP'], $options);
        $this->assertArrayHasKey('image_sizes', $options);
        $this->assertArrayHasKey('42', $options['image_sizes']);
    }

    /**
     * Tests getting all options without image sizes.
     */
    public function testReturnsAllOptionsWithoutImageSizes()
    {
        $this->expectEvent(ContaoCoreEvents::IMAGE_SIZES_ALL);
        $this->expectImageSizes([]);

        $options = $this->imageSizes->getAllOptions();

        $this->assertArraySubset($GLOBALS['TL_CROP'], $options);
        $this->assertArrayNotHasKey('image_sizes', $options);
    }

    /**
     * Tests getting the options for an admin user.
     */
    public function testReturnsTheAdminUserOptions()
    {
        $this->expectEvent(ContaoCoreEvents::IMAGE_SIZES_USER);
        $this->expectExampleImageSizes();

        $user = $this->createMock(BackendUser::class);
        $user->imageSizes = serialize(['image_sizes' => '42']);
        $user->isAdmin = true;

        $options = $this->imageSizes->getOptionsForUser($user);

        // TL_CROP would not be returned if the admin check was not done (because it's not in the allowed imageSizes)
        $this->assertArraySubset($GLOBALS['TL_CROP'], $options);
    }

    /**
     * Tests getting all options for a regular user.
     */
    public function testReturnsTheRegularUserOptions()
    {
        $this->expectEvent(ContaoCoreEvents::IMAGE_SIZES_USER);
        $this->expectExampleImageSizes();

        $user = $this->createMock(BackendUser::class);
        $user->isAdmin = false;

        // Allow only one image size
        $user->imageSizes = serialize([42]);
        $options = $this->imageSizes->getOptionsForUser($user);

        $this->assertArrayNotHasKey('relative', $options);
        $this->assertArrayNotHasKey('exact', $options);
        $this->assertArrayHasKey('image_sizes', $options);
        $this->assertArrayHasKey('42', $options['image_sizes']);

        // Allow only some TL_CROP options
        $user->imageSizes = serialize(['proportional', 'box']);
        $options = $this->imageSizes->getOptionsForUser($user);

        $this->assertArrayHasKey('relative', $options);
        $this->assertArrayNotHasKey('exact', $options);
        $this->assertArrayNotHasKey('image_sizes', $options);

        // Allow nothing
        $user->imageSizes = serialize([]);
        $options = $this->imageSizes->getOptionsForUser($user);

        $this->assertSame([], $options);
    }

    /**
     * Adds an expected method call to the event dispatcher mock object.
     *
     * @param string $event
     */
    private function expectEvent($event)
    {
        $this->eventDispatcher
            ->expects($this->atLeastOnce())
            ->method('dispatch')
            ->with($event, $this->isInstanceOf('Contao\CoreBundle\Event\ImageSizesEvent'))
        ;
    }

    /**
     * Adds an expected method call to the database connection mock object.
     *
     * @param array $imageSizes
     */
    private function expectImageSizes(array $imageSizes)
    {
        $this->connection
            ->expects($this->atLeastOnce())
            ->method('fetchAll')
            ->willReturn($imageSizes);
    }

    /**
     * Adds expected example image sizes to the database connection mock object.
     */
    private function expectExampleImageSizes()
    {
        $this->expectImageSizes(
            [
                [
                    'id' => '42',
                    'name' => 'foobar',
                    'width' => '',
                    'height' => '',
                ],
            ]
        );
    }
}
