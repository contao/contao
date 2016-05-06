<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\NewsBundle\Test\EventListener;

use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\NewsBundle\EventListener\GeneratePageListener;
use Contao\Model\Collection;

/**
 * Tests the GeneratePageListener class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class GeneratePageListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $listener = new GeneratePageListener($this->mockContaoFramework());

        $this->assertInstanceOf('Contao\NewsBundle\EventListener\GeneratePageListener', $listener);
    }

    /**
     * Tests that the listener returns a replacement string for a calendar feed.
     */
    public function testOnGeneratePage()
    {
        $pageModel = $this
            ->getMockBuilder('Contao\PageModel')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $layoutModel = $this
            ->getMockBuilder('Contao\LayoutModel')
            ->setMethods(['__get'])
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $layoutModel
            ->expects($this->any())
            ->method('__get')
            ->willReturnCallback(function ($key) {
                switch ($key) {
                    case 'newsfeeds':
                        return 'a:1:{i:0;i:3;}';

                    default:
                        return null;
                }
            })
        ;

        $GLOBALS['TL_HEAD'] = [];

        $listener = new GeneratePageListener($this->mockContaoFramework());
        $listener->onGeneratePage($pageModel, $layoutModel);

        $this->assertEquals(
            [
                '<link type="application/rss+xml" rel="alternate" href="http://localhost/share/news.xml" title="Latest news">',
            ],
            $GLOBALS['TL_HEAD']
        );
    }

    /**
     * Tests that the listener returns if there are no feeds.
     */
    public function testReturnIfNoFeeds()
    {
        $pageModel = $this
            ->getMockBuilder('Contao\PageModel')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $layoutModel = $this
            ->getMockBuilder('Contao\LayoutModel')
            ->setMethods(['__get'])
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $layoutModel
            ->expects($this->any())
            ->method('__get')
            ->willReturnCallback(function ($key) {
                switch ($key) {
                    case 'newsfeeds':
                        return '';

                    default:
                        return null;
                }
            })
        ;

        $GLOBALS['TL_HEAD'] = [];

        $listener = new GeneratePageListener($this->mockContaoFramework());
        $listener->onGeneratePage($pageModel, $layoutModel);

        $this->assertEmpty($GLOBALS['TL_HEAD']);
    }

    /**
     * Tests that the listener returns if there are no models.
     */
    public function testReturnIfNoModels()
    {
        $pageModel = $this
            ->getMockBuilder('Contao\PageModel')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $layoutModel = $this
            ->getMockBuilder('Contao\LayoutModel')
            ->setMethods(['__get'])
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $layoutModel
            ->expects($this->any())
            ->method('__get')
            ->willReturnCallback(function ($key) {
                switch ($key) {
                    case 'newsfeeds':
                        return 'a:1:{i:0;i:3;}';

                    default:
                        return null;
                }
            })
        ;

        $GLOBALS['TL_HEAD'] = [];

        $listener = new GeneratePageListener($this->mockContaoFramework(true));
        $listener->onGeneratePage($pageModel, $layoutModel);

        $this->assertEmpty($GLOBALS['TL_HEAD']);
    }

    /**
     * Returns a ContaoFramework instance.
     *
     * @param bool $noModels
     *
     * @return ContaoFrameworkInterface
     */
    private function mockContaoFramework($noModels = false)
    {
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

        $feedModel = $this
            ->getMockBuilder('Contao\NewsFeedModel')
            ->setMethods(['__get'])
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $feedModel
            ->expects($this->any())
            ->method('__get')
            ->willReturnCallback(function ($key) {
                switch ($key) {
                    case 'feedBase':
                        return 'http://localhost/';

                    case 'alias':
                        return 'news';

                    case 'format':
                        return 'rss';

                    case 'title':
                        return 'Latest news';

                    default:
                        return null;
                }
            })
        ;

        $newsFeedModelAdapter = $this
            ->getMockBuilder('Contao\CoreBundle\Framework\Adapter')
            ->setMethods(['findByIds'])
            ->setConstructorArgs(['Contao\NewsFeedModel'])
            ->getMock()
        ;

        $newsFeedModelAdapter
            ->expects($this->any())
            ->method('findByIds')
            ->willReturn($noModels ? null : new Collection([$feedModel], 'tl_news_feeds'))
        ;

        $framework
            ->expects($this->any())
            ->method('getAdapter')
            ->willReturnCallback(function ($key) use ($newsFeedModelAdapter) {
                switch ($key) {
                    case 'Contao\NewsFeedModel':
                        return $newsFeedModelAdapter;

                    case 'Contao\Template':
                        return new Adapter('Contao\Template');

                    default:
                        return null;
                }
            })
        ;

        return $framework;
    }
}
