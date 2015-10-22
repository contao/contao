<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Image;

use Contao\CoreBundle\Event\ContaoCoreEvents;
use Contao\CoreBundle\Image\ImageSizes;
use Contao\CoreBundle\Test\TestCase;

/**
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class ImageSizesTest extends TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Doctrine\DBAL\Connection
     */
    private $db;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var ImageSizes
     */
    private $imageSizes;

    public function setUp()
    {
        require_once __DIR__ . '/../../src/Resources/contao/config/config.php';

        $this->db              = $this->getMock('Doctrine\\DBAL\\Connection', ['fetchAll'], [], '', false);
        $this->eventDispatcher = $this->getMock('Symfony\\Component\\EventDispatcher\\EventDispatcherInterface');
        $this->imageSizes      = new ImageSizes($this->db, $this->eventDispatcher, $this->mockContaoFramework());
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\\CoreBundle\\Image\\ImageSizes', $this->imageSizes);
    }

    public function testGetAllOptionsWithImageSizes()
    {
        $this->expectEvent(ContaoCoreEvents::IMAGE_SIZES_ALL);
        $this->returnExampleImageSizes();

        $options = $this->imageSizes->getAllOptions();

        $this->assertArraySubset($GLOBALS['TL_CROP'], $options);
        $this->assertArrayHasKey('image_sizes', $options);
        $this->assertArrayHasKey('42', $options['image_sizes']);
    }

    public function testGetAllOptionsWithoutImageSizes()
    {
        $this->expectEvent(ContaoCoreEvents::IMAGE_SIZES_ALL);
        $this->returnImageSizes([]);

        $options = $this->imageSizes->getAllOptions();

        $this->assertArraySubset($GLOBALS['TL_CROP'], $options);
        $this->assertArrayNotHasKey('image_sizes', $options);
    }

    public function testGetOptionsForAdminUser()
    {
        $this->expectEvent(ContaoCoreEvents::IMAGE_SIZES_USER);
        $this->returnExampleImageSizes();

        /** @var \PHPUnit_Framework_MockObject_MockObject|\BackendUser $user */
        $user = $this->getMock('Contao\\BackendUser');
        $user->imageSizes = serialize(['image_sizes' => '42']);
        $user->isAdmin = true;

        $options = $this->imageSizes->getOptionsForUser($user);

        // TL_CROP would not be returned if the admin check was not done (because it's not in the allowed imageSizes)
        $this->assertArraySubset($GLOBALS['TL_CROP'], $options);
    }

    public function testGetOptionsForRegularUser()
    {
        $this->expectEvent(ContaoCoreEvents::IMAGE_SIZES_USER);
        $this->returnExampleImageSizes();

        /** @var \PHPUnit_Framework_MockObject_MockObject|\BackendUser $user */
        $user = $this->getMock('Contao\\BackendUser');
        $user->isAdmin = false;

        // Only an image size allowed
        $user->imageSizes = serialize(['42']);
        $options = $this->imageSizes->getOptionsForUser($user);

        $this->assertArrayNotHasKey('relative', $options);
        $this->assertArrayNotHasKey('exact', $options);
        $this->assertArrayHasKey('image_sizes', $options);
        $this->assertArrayHasKey('42', $options['image_sizes']);

        // Only some TL_CROP options allowed
        $user->imageSizes = serialize(['proportional', 'box']);
        $options = $this->imageSizes->getOptionsForUser($user);

        $this->assertArrayHasKey('relative', $options);
        $this->assertArrayNotHasKey('exact', $options);
        $this->assertArrayNotHasKey('image_sizes', $options);

        // Nothing allowed
        $user->imageSizes = serialize([]);
        $options = $this->imageSizes->getOptionsForUser($user);

        $this->assertEquals([], $options);
    }

    private function expectEvent($event)
    {
        $this->eventDispatcher
            ->expects($this->atLeastOnce())
            ->method('dispatch')
            ->with(
                $event,
                $this->isInstanceOf('Contao\\CoreBundle\\Event\\ImageSizesEvent')
            )
        ;
    }

    private function returnImageSizes(array $imageSizes)
    {
        $this->db
            ->expects($this->atLeastOnce())
            ->method('fetchAll')
            ->willReturn($imageSizes);
        ;
    }

    private function returnExampleImageSizes()
    {
        $this->returnImageSizes(
            [
                [
                    'id'     => '42',
                    'name'   => 'foobar',
                    'width'  => '',
                    'height' => '',
                ]
            ]
        );
    }
}
