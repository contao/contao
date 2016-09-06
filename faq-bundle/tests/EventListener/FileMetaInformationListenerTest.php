<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\FaqBundle\Test\EventListener;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\FaqBundle\EventListener\FileMetaInformationListener;
use Contao\FaqCategoryModel;
use Contao\PageModel;

/**
 * Tests the FileMetaInformationListener class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class FileMetaInformationListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $listener = new FileMetaInformationListener($this->mockContaoFramework());

        $this->assertInstanceOf('Contao\FaqBundle\EventListener\FileMetaInformationListener', $listener);
    }

    /**
     * Tests that the listener returns a page model.
     */
    public function testReturnPageModel()
    {
        $listener = new FileMetaInformationListener($this->mockContaoFramework());

        $this->assertInstanceOf(
            'Contao\PageModel',
            $listener->onAddFileMetaInformationToRequest('tl_faq_category', 2)
        );
    }

    /**
     * Tests that the listener returns false if the table is unknown.
     */
    public function testReturnFalseIfTableUnknown()
    {
        $listener = new FileMetaInformationListener($this->mockContaoFramework());

        $this->assertFalse($listener->onAddFileMetaInformationToRequest('invalid', 2));
    }

    /**
     * Returns a ContaoFramework instance.
     *
     * @return ContaoFrameworkInterface
     */
    private function mockContaoFramework()
    {
        /** @var PageModel|\PHPUnit_Framework_MockObject_MockObject $pageModel */
        $pageModel = $this
            ->getMockBuilder('Contao\PageModel')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        /** @var FaqCategoryModel|\PHPUnit_Framework_MockObject_MockObject $pageModel */
        $categoryModel = $this
            ->getMockBuilder('Contao\FaqCategoryModel')
            ->setMethods(['getRelated'])
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $categoryModel
            ->expects($this->any())
            ->method('getRelated')
            ->willReturn($pageModel)
        ;

        $categoryAdapter = $this
            ->getMockBuilder('Contao\CoreBundle\Framework\Adapter')
            ->setMethods(['findByPk'])
            ->setConstructorArgs(['Contao\FaqCategoryModel'])
            ->getMock()
        ;

        $categoryAdapter
            ->expects($this->any())
            ->method('findByPk')
            ->willReturn($categoryModel)
        ;

        /** @var ContaoFramework|\PHPUnit_Framework_MockObject_MockObject $framework */
        $framework = $this
            ->getMockBuilder('Contao\CoreBundle\Framework\ContaoFramework')
            ->disableOriginalConstructor()
            ->setMethods(['isInitialized', 'getAdapter'])
            ->getMock()
        ;

        $framework
            ->expects($this->any())
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $framework
            ->expects($this->any())
            ->method('getAdapter')
            ->willReturn($categoryAdapter)
        ;

        return $framework;
    }
}
