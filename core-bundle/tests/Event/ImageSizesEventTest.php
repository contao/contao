<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Event;

use Contao\CoreBundle\Event\ImageSizesEvent;

/**
 * Tests the ImageSizesEvent class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ImageSizesEventTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $event = new ImageSizesEvent([1]);

        $this->assertInstanceOf('Contao\CoreBundle\Event\ImageSizesEvent', $event);
    }

    /**
     * Tests the image sizes setter and getter.
     */
    public function testImageSizesSetterGetter()
    {
        $event = new ImageSizesEvent([1]);

        $this->assertEquals([1], $event->getImageSizes());

        $event->setImageSizes([1, 2]);

        $this->assertEquals([1, 2], $event->getImageSizes());
    }

    /**
     * Tests the getUser() method.
     */
    public function testGetUser()
    {
        $user = $this->getMock('Contao\BackendUser');
        $event = new ImageSizesEvent([1], $user);

        $this->assertEquals($user, $event->getUser());
    }
}
