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
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\News;
use Contao\NewsBundle\EventListener\InsertTagsListener;
use Contao\NewsFeedModel;
use Contao\NewsModel;
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
    public function testCanBeInstantiated()
    {
        $listener = new InsertTagsListener($this->mockContaoFramework());

        $this->assertInstanceOf('Contao\NewsBundle\EventListener\InsertTagsListener', $listener);
    }

    /**
     * Tests that the listener returns a replacement string for a news feed.
     */
    public function testReplacesTheNewsFeedTag()
    {
        $listener = new InsertTagsListener($this->mockContaoFramework());

        $this->assertSame(
            'http://localhost/share/news.xml',
            $listener->onReplaceInsertTags('news_feed::2')
        );
    }

    /**
     * Tests that the listener returns a replacement string for a news item.
     */
    public function testReplacesTheNewsTags()
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
     * Tests that the listener returns false if the tag is unknown.
     */
    public function testReturnsFalseIfTheTagIsUnknown()
    {
        $listener = new InsertTagsListener($this->mockContaoFramework());

        $this->assertFalse($listener->onReplaceInsertTags('link_url::2'));
    }

    /**
     * Tests that the listener returns an empty string if there is no model.
     */
    public function testReturnsAnEmptyStringIfThereIsNoModel()
    {
        $listener = new InsertTagsListener($this->mockContaoFramework('source', true));

        $this->assertSame('', $listener->onReplaceInsertTags('news_feed::3'));
        $this->assertSame('', $listener->onReplaceInsertTags('news_url::3'));
    }

    /**
     * Returns a ContaoFramework instance.
     *
     * @param string $source
     * @param bool   $noModels
     *
     * @return ContaoFrameworkInterface
     */
    private function mockContaoFramework($source = 'default', $noModels = false)
    {
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

        $newsModel = $this->createMock(NewsModel::class);

        $newsModel
            ->method('__get')
            ->willReturnCallback(function ($key) use ($source) {
                switch ($key) {
                    case 'headline':
                        return '"Foo" is not "bar"';

                    case 'teaser':
                        return '<p>Foo does not equal bar.</p>';

                    default:
                        return null;
                }
            })
        ;

        $newsModelAdapter = $this
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['findByIdOrAlias'])
            ->getMock()
        ;

        $newsModelAdapter
            ->method('findByIdOrAlias')
            ->willReturn($noModels ? null : $newsModel)
        ;

        $newsAdapter = $this
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['generateNewsUrl'])
            ->getMock()
        ;

        $newsAdapter
            ->method('generateNewsUrl')
            ->willReturn('news/foo-is-not-bar.html')
        ;

        $framework = $this->createMock(ContaoFrameworkInterface::class);

        $framework
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $framework
            ->method('getAdapter')
            ->willReturnCallback(function ($key) use ($newsFeedModelAdapter, $newsModelAdapter, $newsAdapter) {
                switch ($key) {
                    case NewsFeedModel::class:
                        return $newsFeedModelAdapter;

                    case NewsModel::class:
                        return $newsModelAdapter;

                    case News::class:
                        return $newsAdapter;

                    default:
                        return null;
                }
            })
        ;

        return $framework;
    }
}
