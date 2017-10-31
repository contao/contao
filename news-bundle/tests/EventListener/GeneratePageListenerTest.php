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
use Contao\LayoutModel;
use Contao\Model\Collection;
use Contao\NewsBundle\EventListener\GeneratePageListener;
use Contao\NewsFeedModel;
use Contao\PageModel;
use PHPUnit\Framework\TestCase;

class GeneratePageListenerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $listener = new GeneratePageListener($this->mockContaoFramework());

        $this->assertInstanceOf('Contao\NewsBundle\EventListener\GeneratePageListener', $listener);
    }

    public function testAddsTheNewsFeedLink(): void
    {
        $layoutModel = $this->createMock(LayoutModel::class);

        $layoutModel
            ->method('__get')
            ->willReturnCallback(
                function (string $key): ?string {
                    if ('newsfeeds' === $key) {
                        return 'a:1:{i:0;i:3;}';
                    }

                    return null;
                }
            )
        ;

        $GLOBALS['TL_HEAD'] = [];

        $listener = new GeneratePageListener($this->mockContaoFramework());
        $listener->onGeneratePage($this->createMock(PageModel::class), $layoutModel);

        $this->assertSame(
            [
                '<link type="application/rss+xml" rel="alternate" href="http://localhost/share/news.xml" title="Latest news">',
            ],
            $GLOBALS['TL_HEAD']
        );
    }

    public function testDoesNotAddTheNewsFeedLinkIfThereAreNoFeeds(): void
    {
        $layoutModel = $this->createMock(LayoutModel::class);

        $layoutModel
            ->method('__get')
            ->willReturnCallback(
                function (string $key): ?string {
                    if ('newsfeeds' === $key) {
                        return '';
                    }

                    return null;
                }
            )
        ;

        $GLOBALS['TL_HEAD'] = [];

        $listener = new GeneratePageListener($this->mockContaoFramework());
        $listener->onGeneratePage($this->createMock(PageModel::class), $layoutModel);

        $this->assertEmpty($GLOBALS['TL_HEAD']);
    }

    public function testDoesNotAddTheNewsFeedLinkIfThereAreNoModels(): void
    {
        $layoutModel = $this->createMock(LayoutModel::class);

        $layoutModel
            ->method('__get')
            ->willReturnCallback(
                function (string $key): ?string {
                    if ('newsfeeds' === $key) {
                        return 'a:1:{i:0;i:3;}';
                    }

                    return null;
                }
            )
        ;

        $GLOBALS['TL_HEAD'] = [];

        $listener = new GeneratePageListener($this->mockContaoFramework(true));
        $listener->onGeneratePage($this->createMock(PageModel::class), $layoutModel);

        $this->assertEmpty($GLOBALS['TL_HEAD']);
    }

    /**
     * Mocks the Contao framework.
     *
     * @param bool $noModels
     *
     * @return ContaoFrameworkInterface
     */
    private function mockContaoFramework(bool $noModels = false): ContaoFrameworkInterface
    {
        $framework = $this->createMock(ContaoFrameworkInterface::class);

        $framework
            ->method('isInitialized')
            ->willReturn(true)
        ;

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

                        case 'format':
                            return 'rss';

                        case 'title':
                            return 'Latest news';
                    }

                    return null;
                }
            )
        ;

        $newsFeedModelAdapter = $this->createMock(Adapter::class);

        $newsFeedModelAdapter
            ->method('__call')
            ->with('findByIds')
            ->willReturn($noModels ? null : new Collection([$feedModel], 'tl_news_feeds'))
        ;

        $framework
            ->method('getAdapter')
            ->willReturnCallback(
                function (string $key) use ($newsFeedModelAdapter): ?Adapter {
                    switch ($key) {
                        case 'Contao\NewsFeedModel':
                            return $newsFeedModelAdapter;

                        case 'Contao\Template':
                            return new Adapter('Contao\Template');
                    }

                    return null;
                }
            )
        ;

        return $framework;
    }
}
