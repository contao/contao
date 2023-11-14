<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsletterBundle\Tests\EventListener;

use Contao\CoreBundle\Event\SitemapEvent;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\Database;
use Contao\NewsletterBundle\EventListener\SitemapListener;
use Contao\NewsletterChannelModel;
use Contao\NewsletterModel;
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

    public function testNothingIsAddedIfNoPublishedChannel(): void
    {
        $adapters = [
            NewsletterChannelModel::class => $this->mockConfiguredAdapter(['findAll' => null]),
        ];

        $sitemapEvent = $this->createSitemapEvent([]);
        $listener = $this->createListener([], $adapters);
        $listener($sitemapEvent);

        $this->assertStringNotContainsString('<url><loc>', (string) $sitemapEvent->getDocument()->saveXML());
    }

    public function testNewsletterIsAdded(): void
    {
        $jumpToPage = $this->mockClassWithProperties(PageModel::class, [
            'published' => 1,
            'protected' => 1,
            'groups' => [1],
        ]);

        $jumpToPage
            ->method('getAbsoluteUrl')
            ->willReturn('https://contao.org')
        ;

        $adapters = [
            NewsletterChannelModel::class => $this->mockConfiguredAdapter([
                'findAll' => [
                    $this->mockClassWithProperties(NewsletterChannelModel::class, [
                        'jumpTo' => 42,
                    ]),
                ],
            ]),
            PageModel::class => $this->mockConfiguredAdapter([
                'findWithDetails' => $jumpToPage,
            ]),
            NewsletterModel::class => $this->mockConfiguredAdapter([
                'findSentByPid' => [
                    $this->mockClassWithProperties(NewsletterModel::class),
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
        $security = $this->createMock(Security::class);

        if ([] !== $allPages) {
            $security
                ->expects($this->once())
                ->method('isGranted')
                ->with(ContaoCorePermissions::MEMBER_IN_GROUPS, [1])
                ->willReturn(true)
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
