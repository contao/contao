<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\NewsBundle\Tests\EventListener;

use Contao\ArticleModel;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\NewsArchiveModel;
use Contao\NewsBundle\EventListener\InsertTagsListener;
use Contao\NewsFeedModel;
use Contao\NewsModel;
use Contao\PageModel;
use PHPUnit\Framework\TestCase;

/**
 * Tests the InsertTagsListener class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class InsertTagsListenerTest extends TestCase
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

        $this->assertSame('', $listener->onReplaceInsertTags('news_feed::3'));
        $this->assertSame('', $listener->onReplaceInsertTags('news_url::3'));
    }

    /**
     * Tests that the listener returns an empty string if there is no calendar model.
     */
    public function testReturnEmptyStringIfNoCalendarModel()
    {
        $listener = new InsertTagsListener($this->mockContaoFramework('source', false, true));

        $this->assertSame('', $listener->onReplaceInsertTags('news_url::3'));
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
        $framework = $this->createMock(ContaoFrameworkInterface::class);

        $framework
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $feedModel = $this->createMock(NewsFeedModel::class);

        $feedModel
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
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['findByPk'])
            ->getMock()
        ;

        $newsFeedModelAdapter
            ->method('findByPk')
            ->willReturn($noModels ? null : $feedModel)
        ;

        $page = $this->createMock(PageModel::class);

        $page
            ->method('getFrontendUrl')
            ->willReturn('news/foo-is-not-bar.html')
        ;

        $newsArchiveModel = $this->createMock(NewsArchiveModel::class);

        $newsArchiveModel
            ->method('getRelated')
            ->willReturn($page)
        ;

        $jumpTo = $this->createMock(PageModel::class);

        $jumpTo
            ->method('getFrontendUrl')
            ->willReturn('internal-target.html')
        ;

        $pid = $this->createMock(PageModel::class);

        $pid
            ->method('getFrontendUrl')
            ->willReturn('portfolio/articles/foobar.html')
        ;

        $articleModel = $this->createMock(ArticleModel::class);

        $articleModel
            ->method('getRelated')
            ->willReturn($pid)
        ;

        $newsModel = $this->createMock(NewsModel::class);

        $newsModel
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
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['findByIdOrAlias'])
            ->getMock()
        ;

        $newModelAdapter
            ->method('findByIdOrAlias')
            ->willReturn($noModels ? null : $newsModel)
        ;

        $framework
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
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['isComplete', 'preload', 'getInstance', 'get'])
            ->getMock()
        ;

        $configAdapter
            ->method('isComplete')
            ->willReturn(true)
        ;

        $configAdapter
            ->method('preload')
            ->willReturn(null)
        ;

        $configAdapter
            ->method('getInstance')
            ->willReturn(null)
        ;

        $configAdapter
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
