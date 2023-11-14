<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\Tests\EventListener;

use Contao\CoreBundle\Event\SitemapEvent;
use Contao\Database;
use Contao\NewsArchiveModel;
use Contao\NewsBundle\EventListener\SitemapListener;
use Contao\NewsModel;
use Contao\PageModel;
use Contao\TestCase\ContaoTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;

class SitemapListenerTest extends ContaoTestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_CONFIG']);

        parent::tearDown();
    }

    public function testNothingIsAddedIfNoPublishedArchive(): void
    {
        $adapters = [
            NewsArchiveModel::class => $this->mockConfiguredAdapter(['findByProtected' => null]),
        ];

        $sitemapEvent = $this->createSitemapEvent([]);
        $listener = $this->createListener([], $adapters);
        $listener($sitemapEvent);

        $this->assertStringNotContainsString('<url><loc>', (string) $sitemapEvent->getDocument()->saveXML());
    }

    public function testNewsArticleIsAdded(): void
    {
        $jumpToPage = $this->mockClassWithProperties(PageModel::class, [
            'published' => 1,
            'protected' => 0,
        ]);

        $jumpToPage
            ->method('getAbsoluteUrl')
            ->willReturn('https://contao.org')
        ;

        $adapters = [
            NewsArchiveModel::class => $this->mockConfiguredAdapter([
                'findByProtected' => [
                    $this->mockClassWithProperties(NewsArchiveModel::class, [
                        'jumpTo' => 42,
                    ]),
                ],
            ]),
            PageModel::class => $this->mockConfiguredAdapter([
                'findWithDetails' => $jumpToPage,
            ]),
            NewsModel::class => $this->mockConfiguredAdapter([
                'findPublishedDefaultByPid' => [
                    $this->mockClassWithProperties(NewsModel::class, [
                        'jumpTo' => 42,
                    ]),
                ],
            ]),
        ];

        $sitemapEvent = $this->createSitemapEvent([1]);
        $listener = $this->createListener([1, 42], $adapters);
        $listener($sitemapEvent);

        $this->assertStringContainsString('<url><loc>https://contao.org</loc></url>', (string) $sitemapEvent->getDocument()->saveXML());
    }

    private function createListener(array $allPages, array $adapters): SitemapListener
    {
        $database = $this->createMock(Database::class);
        $database
            ->method('getChildRecords')
            ->willReturn($allPages)
        ;

        $instances = [
            Database::class => $database,
        ];

        $framework = $this->mockContaoFramework($adapters, $instances);

        return new SitemapListener($framework, $this->createMock(Security::class));
    }

    private function createSitemapEvent(array $rootPages): SitemapEvent
    {
        $sitemap = new \DOMDocument('1.0', 'UTF-8');
        $urlSet = $sitemap->createElementNS('https://www.sitemaps.org/schemas/sitemap/0.9', 'urlset');
        $sitemap->appendChild($urlSet);

        return new SitemapEvent($sitemap, new Request(), $rootPages);
    }
}
