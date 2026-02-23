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
use Contao\CoreBundle\Routing\ContentUrlGenerator;
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
            NewsletterChannelModel::class => $this->createConfiguredAdapterStub(['findAll' => null]),
        ];

        $sitemapEvent = $this->createSitemapEvent([]);
        $listener = $this->createListener([], $adapters);
        $listener($sitemapEvent);

        $this->assertStringNotContainsString('<url><loc>', (string) $sitemapEvent->getDocument()->saveXML());
    }

    public function testNewsletterIsAdded(): void
    {
        $jumpToPage = $this->createClassWithPropertiesStub(PageModel::class, [
            'published' => 1,
            'protected' => 1,
            'groups' => [1],
        ]);

        $adapters = [
            NewsletterChannelModel::class => $this->createConfiguredAdapterStub([
                'findAll' => [
                    $this->createClassWithPropertiesStub(NewsletterChannelModel::class, ['jumpTo' => 42]),
                ],
            ]),
            PageModel::class => $this->createConfiguredAdapterStub([
                'findWithDetails' => $jumpToPage,
            ]),
            NewsletterModel::class => $this->createConfiguredAdapterStub([
                'findSentByPid' => [
                    $this->createClassWithPropertiesStub(NewsletterModel::class),
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
        $database = $this->createStub(Database::class);
        $database
            ->method('getChildRecords')
            ->willReturn($allPages)
        ;

        $instances = [
            Database::class => $database,
        ];

        $framework = $this->createContaoFrameworkStub($adapters, $instances);

        if ([] === $allPages) {
            $security = $this->createStub(Security::class);
        } else {
            $security = $this->createMock(Security::class);
            $security
                ->expects($this->once())
                ->method('isGranted')
                ->with(ContaoCorePermissions::MEMBER_IN_GROUPS, [1])
                ->willReturn(true)
            ;
        }

        $urlGenerator = $this->createStub(ContentUrlGenerator::class);
        $urlGenerator
            ->method('generate')
            ->willReturn('https://contao.org')
        ;

        return new SitemapListener($framework, $security, $urlGenerator);
    }

    private function createSitemapEvent(array $rootPages): SitemapEvent
    {
        $sitemap = new \DOMDocument('1.0', 'UTF-8');
        $urlSet = $sitemap->createElementNS('https://www.sitemaps.org/schemas/sitemap/0.9', 'urlset');
        $sitemap->appendChild($urlSet);

        return new SitemapEvent($sitemap, new Request(), $rootPages);
    }
}
