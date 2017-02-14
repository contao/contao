<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\NewsBundle\Tests\EventListener;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\NewsArchiveModel;
use Contao\NewsBundle\EventListener\FileMetaInformationListener;
use Contao\NewsModel;
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

        $this->assertInstanceOf('Contao\NewsBundle\EventListener\FileMetaInformationListener', $listener);
    }

    /**
     * Tests that the listener returns a page model.
     */
    public function testReturnPageModel()
    {
        $listener = new FileMetaInformationListener($this->mockContaoFramework());

        $this->assertInstanceOf(
            'Contao\PageModel',
            $listener->onAddFileMetaInformationToRequest('tl_news_archive', 2)
        );

        $this->assertInstanceOf(
            'Contao\PageModel',
            $listener->onAddFileMetaInformationToRequest('tl_news', 2)
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

        /** @var NewsArchiveModel|\PHPUnit_Framework_MockObject_MockObject $pageModel */
        $archiveModel = $this
            ->getMockBuilder('Contao\NewsArchiveModel')
            ->setMethods(['getRelated'])
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $archiveModel
            ->expects($this->any())
            ->method('getRelated')
            ->willReturn($pageModel)
        ;

        $archiveAdapter = $this
            ->getMockBuilder('Contao\CoreBundle\Framework\Adapter')
            ->setMethods(['findByPk'])
            ->setConstructorArgs(['Contao\NewsArchiveModel'])
            ->getMock()
        ;

        $archiveAdapter
            ->expects($this->any())
            ->method('findByPk')
            ->willReturn($archiveModel)
        ;

        /** @var NewsModel|\PHPUnit_Framework_MockObject_MockObject $pageModel */
        $newsModel = $this
            ->getMockBuilder('Contao\NewsModel')
            ->setMethods(['getRelated'])
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $newsModel
            ->expects($this->any())
            ->method('getRelated')
            ->willReturn($archiveModel)
        ;

        $newsAdapter = $this
            ->getMockBuilder('Contao\CoreBundle\Framework\Adapter')
            ->setMethods(['findByPk'])
            ->setConstructorArgs(['Contao\NewsModel'])
            ->getMock()
        ;

        $newsAdapter
            ->expects($this->any())
            ->method('findByPk')
            ->willReturn($newsModel)
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
            ->willReturnCallback(function ($key) use ($archiveAdapter, $newsAdapter) {
                switch ($key) {
                    case 'Contao\NewsArchiveModel':
                        return $archiveAdapter;

                    case 'Contao\NewsModel':
                        return $newsAdapter;

                    default:
                        return null;
                }
            })
        ;

        return $framework;
    }
}
