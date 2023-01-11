<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Event;

use Contao\CoreBundle\Event\SitemapEvent;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class SitemapEventTest extends TestCase
{
    public function testAddingUrlsToExistingUrlSet(): void
    {
        $sitemap = new \DOMDocument('1.0', 'UTF-8');
        $urlSet = $sitemap->createElementNS('https://www.sitemaps.org/schemas/sitemap/0.9', 'urlset');
        $sitemap->appendChild($urlSet);

        $event = new SitemapEvent($sitemap, new Request(), []);
        $event->addUrlToDefaultUrlSet('https://contao.org');

        $this->assertStringContainsString('<urlset xmlns="https://www.sitemaps.org/schemas/sitemap/0.9"><url><loc>https://contao.org</loc></url></urlset>', (string) $event->getDocument()->saveXML());
    }

    public function testAddingUrlsToExistingUrlSetDoesNotFailIfThereIsNoUrlSet(): void
    {
        $sitemap = new \DOMDocument('1.0', 'UTF-8');
        $sitemap->preserveWhiteSpace = false;
        $event = new SitemapEvent($sitemap, new Request(), []);
        $event->addUrlToDefaultUrlSet('https://contao.org');

        $this->assertStringNotContainsString('<urlset xmlns="https://www.sitemaps.org/schemas/sitemap/0.9"><url><loc>https://contao.org</loc></url></urlset>', (string) $event->getDocument()->saveXML());
    }
}
