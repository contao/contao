<?php

declare(strict_types=1);

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

class InsertTagsListenerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $listener = new InsertTagsListener($this->mockContaoFramework());

        $this->assertInstanceOf('Contao\NewsBundle\EventListener\InsertTagsListener', $listener);
    }

    public function testReplacesTheNewsFeedTag(): void
    {
        $listener = new InsertTagsListener($this->mockContaoFramework());

        $this->assertSame(
            'http://localhost/share/news.xml',
            $listener->onReplaceInsertTags('news_feed::2')
        );
    }

    public function testReplacesTheNewsTags(): void
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

    public function testReturnsFalseIfTheTagIsUnknown(): void
    {
        $listener = new InsertTagsListener($this->mockContaoFramework());

        $this->assertFalse($listener->onReplaceInsertTags('link_url::2'));
    }

    public function testReturnsAnEmptyStringIfThereIsNoModel(): void
    {
        $listener = new InsertTagsListener($this->mockContaoFramework('source', true));

        $this->assertSame('', $listener->onReplaceInsertTags('news_feed::3'));
        $this->assertSame('', $listener->onReplaceInsertTags('news_url::3'));
    }

    /**
     * Mocks the Contao framework.
     *
     * @param string $source
     * @param bool   $noModels
     *
     * @return ContaoFrameworkInterface
     */
    private function mockContaoFramework(string $source = 'default', bool $noModels = false): ContaoFrameworkInterface
    {
        $feedModel = $this->createMock(NewsFeedModel::class);

        $feedModel
            ->method('__get')
            ->willReturnCallback(
                function (string $key): ?string {
                    switch ($key) {
                        case 'feedBase':
                            return 'http://localhost/';

                        case 'alias':
                            return 'news';
                    }

                    return null;
                }
            )
        ;

        $newsFeedModelAdapter = $this->createMock(Adapter::class);

        $newsFeedModelAdapter
            ->method('__call')
            ->with('findByPk')
            ->willReturn($noModels ? null : $feedModel)
        ;

        $newsModel = $this->createMock(NewsModel::class);

        $newsModel
            ->method('__get')
            ->willReturnCallback(
                function (string $key) use ($source): ?string {
                    switch ($key) {
                        case 'headline':
                            return '"Foo" is not "bar"';

                        case 'teaser':
                            return '<p>Foo does not equal bar.</p>';
                    }

                    return null;
                }
            )
        ;

        $newsModelAdapter = $this->createMock(Adapter::class);

        $newsModelAdapter
            ->method('__call')
            ->with('findByIdOrAlias')
            ->willReturn($noModels ? null : $newsModel)
        ;

        $newsAdapter = $this->createMock(Adapter::class);

        $newsAdapter
            ->method('__call')
            ->with('generateNewsUrl')
            ->willReturn('news/foo-is-not-bar.html')
        ;

        $framework = $this->createMock(ContaoFrameworkInterface::class);

        $framework
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $framework
            ->method('getAdapter')
            ->willReturnCallback(
                function (string $key) use ($newsFeedModelAdapter, $newsModelAdapter, $newsAdapter): ?Adapter {
                    switch ($key) {
                        case NewsFeedModel::class:
                            return $newsFeedModelAdapter;

                        case NewsModel::class:
                            return $newsModelAdapter;

                        case News::class:
                            return $newsAdapter;
                    }

                    return null;
                }
            )
        ;

        return $framework;
    }
}
