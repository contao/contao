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
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\Database;
use Contao\NewsArchiveModel;
use Contao\NewsBundle\EventListener\SitemapListener;
use Contao\NewsModel;
use Contao\PageModel;
use Contao\TestCase\ContaoTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
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
            NewsArchiveModel::class => $this->createConfiguredAdapterStub(['findByProtected' => null]),
        ];

        $sitemapEvent = $this->createSitemapEvent([]);
        $listener = $this->createListener([], $adapters);
        $listener($sitemapEvent);

        $this->assertStringNotContainsString('<url><loc>', (string) $sitemapEvent->getDocument()->saveXML());
    }

    #[DataProvider('getNewsArticles')]
    public function testNewsArticleIsAdded(array $pageProperties, array $newsArchiveProperties, bool $hasAuthenticatedMember): void
    {
        $newsArchive = $this->createClassWithPropertiesStub(NewsArchiveModel::class, $newsArchiveProperties);

        $adapters = [
            NewsArchiveModel::class => $this->createConfiguredAdapterStub([
                'findByProtected' => [$newsArchive],
                'findAll' => [$newsArchive],
            ]),
            PageModel::class => $this->createConfiguredAdapterStub([
                'findWithDetails' => $this->createClassWithPropertiesStub(PageModel::class, $pageProperties),
            ]),
            NewsModel::class => $this->createConfiguredAdapterStub([
                'findPublishedDefaultByPid' => [
                    $this->createClassWithPropertiesStub(NewsModel::class, ['jumpTo' => 42]),
                ],
            ]),
        ];

        $sitemapEvent = $this->createSitemapEvent([1]);
        $listener = $this->createListener([1, 42], $adapters, $hasAuthenticatedMember);
        $listener($sitemapEvent);

        $this->assertStringContainsString('<url><loc>https://contao.org</loc></url>', (string) $sitemapEvent->getDocument()->saveXML());
    }

    public static function getNewsArticles(): iterable
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
                ->expects($this->atLeastOnce())
                ->method('isGranted')
                ->willReturn($hasAuthenticatedMember)
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
