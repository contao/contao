<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\NewsBundle\Test\EventListener;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\NewsBundle\EventListener\FileMetaInformationListener;

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
     * Tests that the listener returns a database result.
     */
    public function testReturnDatabaseResult()
    {
        $listener = new FileMetaInformationListener($this->mockContaoFramework());

        $this->assertInstanceOf(
            'Contao\Database\Result',
            $listener->onAddFileMetaInformationToRequest('tl_news_archive', 2)
        );

        $this->assertInstanceOf(
            'Contao\Database\Result',
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
        /** @var ContaoFramework|\PHPUnit_Framework_MockObject_MockObject $framework */
        $framework = $this
            ->getMockBuilder('Contao\CoreBundle\Framework\ContaoFramework')
            ->disableOriginalConstructor()
            ->setMethods(['isInitialized', 'createInstance'])
            ->getMock()
        ;

        $framework
            ->expects($this->any())
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $databaseAdapter = $this
            ->getMockBuilder('Contao\CoreBundle\Framework\Adapter')
            ->setMethods(['prepare', 'execute'])
            ->setConstructorArgs(['Contao\Database'])
            ->getMock()
        ;

        $databaseAdapter
            ->expects($this->any())
            ->method('prepare')
            ->willReturn($databaseAdapter)
        ;

        $databaseResult = $this
            ->getMockBuilder('Contao\Database\Result')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $databaseAdapter
            ->expects($this->any())
            ->method('execute')
            ->willReturn($databaseResult)
        ;

        $framework
            ->expects($this->any())
            ->method('createInstance')
            ->willReturn($databaseAdapter)
        ;

        return $framework;
    }
}
