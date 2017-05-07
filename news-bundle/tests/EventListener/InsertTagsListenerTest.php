<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\NewsBundle\Tests\EventListener;

use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\NewsBundle\EventListener\InsertTagsListener;

/**
 * Tests the InsertTagsListener class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class InsertTagsListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $listener = new InsertTagsListener($this->mockContaoFramework());

        $this->assertInstanceOf('Contao\NewsBundle\EventListener\InsertTagsListener', $listener);
    }

    /**
     * Tests that the listener returns a replacement string for a calendar feed.
     */
    public function testReturnFeedReplacementString()
    {
        $listener = new InsertTagsListener($this->mockContaoFramework());

        $this->assertSame(
            'http://localhost/share/news.xml',
            $listener->onReplaceInsertTags('news_feed::2')
        );
    }

    /**
     * Tests that the listener returns a replacement string for an event.
     */
    public function testReturnEventReplacementString()
    {
        $listener = new InsertTagsListener($this->mockContaoFramework());

        $this->assertSame(
            '<a href="news/foo-is-not-bar.html" title="&quot;Foo&quot; is not &quot;bar&quot;">"Foo" is not "bar"</a>',
            $listener->onReplaceInsertTags('news::2')
        );

        $this->assertSame(
            '<a href="news/foo-is-not-bar.html" title="&quot;Foo&quot; is not &quot;bar&quot;">',
            $listener->onReplaceInsertTags('news_open::2')
        );

        $this->assertSame(
            'news/foo-is-not-bar.html',
            $listener->onReplaceInsertTags('news_url::2')
        );

        $this->assertSame(
            '&quot;Foo&quot; is not &quot;bar&quot;',
            $listener->onReplaceInsertTags('news_title::2')
        );

        $this->assertSame(
            '<p>Foo does not equal bar.</p>',
            $listener->onReplaceInsertTags('news_teaser::2')
        );
    }

    /**
     * Tests that the listener returns a replacement string for an event with an exernal target.
     */
    public function testReturnEventWithExternalTargetReplacementString()
    {
        $listener = new InsertTagsListener($this->mockContaoFramework('external'));

        $this->assertSame(
            '<a href="https://contao.org" title="&quot;Foo&quot; is not &quot;bar&quot;">"Foo" is not "bar"</a>',
            $listener->onReplaceInsertTags('news::2')
        );

        $this->assertSame(
            '<a href="https://contao.org" title="&quot;Foo&quot; is not &quot;bar&quot;">',
            $listener->onReplaceInsertTags('news_open::2')
        );

        $this->assertSame(
            'https://contao.org',
            $listener->onReplaceInsertTags('news_url::2')
        );

        $this->assertSame(
            '&quot;Foo&quot; is not &quot;bar&quot;',
            $listener->onReplaceInsertTags('news_title::2')
        );

        $this->assertSame(
            '<p>Foo does not equal bar.</p>',
            $listener->onReplaceInsertTags('news_teaser::2')
        );
    }

    /**
     * Tests that the listener returns a replacement string for an event with an internal target.
     */
    public function testReturnEventWithInteralTargetReplacementString()
    {
        $listener = new InsertTagsListener($this->mockContaoFramework('internal'));

        $this->assertSame(
            '<a href="internal-target.html" title="&quot;Foo&quot; is not &quot;bar&quot;">"Foo" is not "bar"</a>',
            $listener->onReplaceInsertTags('news::2')
        );

        $this->assertSame(
            '<a href="internal-target.html" title="&quot;Foo&quot; is not &quot;bar&quot;">',
            $listener->onReplaceInsertTags('news_open::2')
        );

        $this->assertSame(
            'internal-target.html',
            $listener->onReplaceInsertTags('news_url::2')
        );

        $this->assertSame(
            '&quot;Foo&quot; is not &quot;bar&quot;',
            $listener->onReplaceInsertTags('news_title::2')
        );

        $this->assertSame(
            '<p>Foo does not equal bar.</p>',
            $listener->onReplaceInsertTags('news_teaser::2')
        );
    }

    /**
     * Tests that the listener returns a replacement string for an event with an article target.
     */
    public function testReturnEventWithArticleTargetReplacementString()
    {
        $listener = new InsertTagsListener($this->mockContaoFramework('article'));

        $this->assertSame(
            '<a href="portfolio/articles/foobar.html" title="&quot;Foo&quot; is not &quot;bar&quot;">"Foo" is not "bar"</a>',
            $listener->onReplaceInsertTags('news::2')
        );

        $this->assertSame(
            '<a href="portfolio/articles/foobar.html" title="&quot;Foo&quot; is not &quot;bar&quot;">',
            $listener->onReplaceInsertTags('news_open::2')
        );

        $this->assertSame(
            'portfolio/articles/foobar.html',
            $listener->onReplaceInsertTags('news_url::2')
        );

        $this->assertSame(
            '&quot;Foo&quot; is not &quot;bar&quot;',
            $listener->onReplaceInsertTags('news_title::2')
        );

        $this->assertSame(
            '<p>Foo does not equal bar.</p>',
            $listener->onReplaceInsertTags('news_teaser::2')
        );
    }

    /**
     * Tests that the listener returns false if the tag is unknown.
     */
    public function testReturnFalseIfTagUnknown()
    {
        $listener = new InsertTagsListener($this->mockContaoFramework());

        $this->assertFalse($listener->onReplaceInsertTags('link_url::2'));
    }

    /**
     * Tests that the listener returns an empty string if there is no model.
     */
    public function testReturnEmptyStringIfNoModel()
    {
        $listener = new InsertTagsListener($this->mockContaoFramework('source', true));

        $this->assertTrue('' === $listener->onReplaceInsertTags('news_feed::3'));
        $this->assertTrue('' === $listener->onReplaceInsertTags('news_url::3'));
    }

    /**
     * Tests that the listener returns an empty string if there is no calendar model.
     */
    public function testReturnEmptyStringIfNoCalendarModel()
    {
        $listener = new InsertTagsListener($this->mockContaoFramework('source', false, true));

        $this->assertTrue('' === $listener->onReplaceInsertTags('news_url::3'));
    }

    /**
     * Returns a ContaoFramework instance.
     *
     * @param string $source
     * @param bool   $noModels
     * @param bool   $noArchive
     *
     * @return ContaoFrameworkInterface
     */
    private function mockContaoFramework($source = 'default', $noModels = false, $noArchive = false)
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

                    default:
                        return null;
                }
            })
        ;

        $newsFeedModelAdapter = $this
            ->getMockBuilder('Contao\CoreBundle\Framework\Adapter')
            ->setMethods(['findByPk'])
            ->setConstructorArgs(['Contao\NewsFeedModel'])
            ->getMock()
        ;

        $newsFeedModelAdapter
            ->expects($this->any())
            ->method('findByPk')
            ->willReturn($noModels ? null : $feedModel)
        ;

        $page = $this
            ->getMockBuilder('Contao\PageModel')
            ->setMethods(['getFrontendUrl'])
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $page
            ->expects($this->any())
            ->method('getFrontendUrl')
            ->willReturn('news/foo-is-not-bar.html')
        ;

        $newsArchiveModel = $this
            ->getMockBuilder('Contao\NewsArchiveModel')
            ->setMethods(['getRelated'])
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $newsArchiveModel
            ->expects($this->any())
            ->method('getRelated')
            ->willReturn($page)
        ;

        $jumpTo = $this
            ->getMockBuilder('Contao\PageModel')
            ->setMethods(['getFrontendUrl'])
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $jumpTo
            ->expects($this->any())
            ->method('getFrontendUrl')
            ->willReturn('internal-target.html')
        ;

        $pid = $this
            ->getMockBuilder('Contao\PageModel')
            ->setMethods(['getFrontendUrl'])
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $pid
            ->expects($this->any())
            ->method('getFrontendUrl')
            ->willReturn('portfolio/articles/foobar.html')
        ;

        $articleModel = $this
            ->getMockBuilder('Contao\ArticleModel')
            ->setMethods(['getRelated'])
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $articleModel
            ->expects($this->any())
            ->method('getRelated')
            ->willReturn($pid)
        ;

        $newsModel = $this
            ->getMockBuilder('Contao\NewsModel')
            ->setMethods(['getRelated', '__get'])
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $newsModel
            ->expects($this->any())
            ->method('getRelated')
            ->willReturnCallback(function ($key) use ($jumpTo, $articleModel, $newsArchiveModel, $noArchive) {
                switch ($key) {
                    case 'jumpTo':
                        return $jumpTo;

                    case 'articleId':
                        return $articleModel;

                    case 'pid':
                        return $noArchive ? null : $newsArchiveModel;

                    default:
                        return null;
                }
            })
        ;

        $newsModel
            ->expects($this->any())
            ->method('__get')
            ->willReturnCallback(function ($key) use ($source) {
                switch ($key) {
                    case 'source':
                        return $source;

                    case 'id':
                        return 2;

                    case 'alias':
                        return 'foo-is-not-bar';

                    case 'headline':
                        return '"Foo" is not "bar"';

                    case 'teaser':
                        return '<p>Foo does not equal bar.</p>';

                    case 'url':
                        return 'https://contao.org';

                    default:
                        return null;
                }
            })
        ;

        $newModelAdapter = $this
            ->getMockBuilder('Contao\CoreBundle\Framework\Adapter')
            ->setMethods(['findByIdOrAlias'])
            ->setConstructorArgs(['Contao\NewsModel'])
            ->getMock()
        ;

        $newModelAdapter
            ->expects($this->any())
            ->method('findByIdOrAlias')
            ->willReturn($noModels ? null : $newsModel)
        ;

        $framework
            ->expects($this->any())
            ->method('getAdapter')
            ->willReturnCallback(function ($key) use ($newsFeedModelAdapter, $newModelAdapter) {
                switch ($key) {
                    case 'Contao\NewsFeedModel':
                        return $newsFeedModelAdapter;

                    case 'Contao\NewsModel':
                        return $newModelAdapter;

                    case 'Contao\Config':
                        return $this->mockConfigAdapter();

                    default:
                        return null;
                }
            })
        ;

        return $framework;
    }

    /**
     * Mocks a config adapter.
     *
     * @return Adapter|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockConfigAdapter()
    {
        $configAdapter = $this
            ->getMockBuilder('Contao\CoreBundle\Framework\Adapter')
            ->setMethods(['isComplete', 'preload', 'getInstance', 'get'])
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $configAdapter
            ->expects($this->any())
            ->method('isComplete')
            ->willReturn(true)
        ;

        $configAdapter
            ->expects($this->any())
            ->method('preload')
            ->willReturn(null)
        ;

        $configAdapter
            ->expects($this->any())
            ->method('getInstance')
            ->willReturn(null)
        ;

        $configAdapter
            ->expects($this->any())
            ->method('get')
            ->willReturnCallback(function ($key) {
                switch ($key) {
                    case 'characterSet':
                        return 'UTF-8';

                    case 'useAutoItem':
                        return true;

                    default:
                        return null;
                }
            })
        ;

        return $configAdapter;
    }
}
