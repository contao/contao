<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\FaqBundle\Tests\EventListener;

use Contao\CoreBundle\Event\SitemapEvent;
use Contao\Database;
use Contao\FaqBundle\EventListener\SitemapListener;
use Contao\FaqCategoryModel;
use Contao\FaqModel;
use Contao\PageModel;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\HttpFoundation\Request;

class SitemapListenerTest extends ContaoTestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_CONFIG']);

        parent::tearDown();
    }

    public function testNothingIsAddedIfNoPublishedCategory(): void
    {
        $adapters = [
            FaqCategoryModel::class => $this->mockConfiguredAdapter(['findAll' => null]),
        ];

        $sitemapEvent = $this->createSitemapEvent([]);
        $listener = $this->createListener([], $adapters);
        $listener($sitemapEvent);

        $this->assertStringNotContainsString('<url><loc>', (string) $sitemapEvent->getDocument()->saveXML());
    }

    public function testFaqEntryIsAdded(): void
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
            FaqCategoryModel::class => $this->mockConfiguredAdapter([
                'findAll' => [
                    $this->mockClassWithProperties(FaqCategoryModel::class, ['jumpTo' => 42]),
                ],
            ]),
            PageModel::class => $this->mockConfiguredAdapter([
                'findWithDetails' => $jumpToPage,
            ]),
            FaqModel::class => $this->mockConfiguredAdapter([
                'findPublishedByPid' => [
                    $this->mockClassWithProperties(FaqModel::class),
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

        return new SitemapListener($framework);
    }

    private function createSitemapEvent(array $rootPages): SitemapEvent
    {
        $sitemap = new \DOMDocument('1.0', 'UTF-8');
        $urlSet = $sitemap->createElementNS('https://www.sitemaps.org/schemas/sitemap/0.9', 'urlset');
        $sitemap->appendChild($urlSet);

        return new SitemapEvent($sitemap, new Request(), $rootPages);
    }
}
