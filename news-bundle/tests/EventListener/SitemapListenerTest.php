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

    /**
     * @dataProvider getNewsArticles
     */
    public function testNewsArticleIsAdded(array $pageProperties, array $newsArchiveProperties, bool $hasAuthenticatedMember): void
    {
        $jumpToPage = $this->mockClassWithProperties(PageModel::class, $pageProperties);
        $jumpToPage
            ->method('getAbsoluteUrl')
            ->willReturn('https://contao.org')
        ;

        $adapters = [
            NewsArchiveModel::class => $this->mockConfiguredAdapter([
                'findByProtected' => [
                    $this->mockClassWithProperties(NewsArchiveModel::class, $newsArchiveProperties),
                ],
                'findAll' => [
                    $this->mockClassWithProperties(NewsArchiveModel::class, $newsArchiveProperties),
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
        $listener = $this->createListener([1, 42], $adapters, $hasAuthenticatedMember);
        $listener($sitemapEvent);

        $this->assertStringContainsString('<url><loc>https://contao.org</loc></url>', (string) $sitemapEvent->getDocument()->saveXML());
    }

    public function getNewsArticles(): \Generator
    {
        yield [
            [
                'published' => 1,
                'protected' => 0,
            ],
            [
                'jumpTo' => 42,
            ],
            false,
        ];

        yield [
            [
                'published' => 1,
                'protected' => 1,
                'groups' => [1],
            ],
            [
                'jumpTo' => 42,
            ],
            true,
        ];

        yield [
            [
                'published' => 1,
                'protected' => 0,
            ],
            [
                'jumpTo' => 42,
                'protected' => 1,
                'groups' => [1],
            ],
            true,
        ];
    }

    private function createListener(array $allPages, array $adapters, bool $hasAuthenticatedMember = false): SitemapListener
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
        $security = $this->createMock(Security::class);

        if ([] !== $allPages) {
            $security
                ->expects($this->atLeastOnce())
                ->method('isGranted')
                ->willReturn($hasAuthenticatedMember)
            ;
        }

        return new SitemapListener($framework, $security);
    }

    private function createSitemapEvent(array $rootPages): SitemapEvent
    {
        $sitemap = new \DOMDocument('1.0', 'UTF-8');
        $urlSet = $sitemap->createElementNS('https://www.sitemaps.org/schemas/sitemap/0.9', 'urlset');
        $sitemap->appendChild($urlSet);

        return new SitemapEvent($sitemap, new Request(), $rootPages);
    }
}
