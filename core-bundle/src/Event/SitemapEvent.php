<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Event;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

class SitemapEvent extends Event
{
    public function __construct(
        private readonly \DOMDocument $document,
        private readonly Request $request,
        private readonly array $rootPageIds,
    ) {
    }

    public function getDocument(): \DOMDocument
    {
        return $this->document;
    }

    public function addUrlToDefaultUrlSet(string $url): self
    {
        $sitemap = $this->getDocument();
        $urlSet = $sitemap->getElementsByTagNameNS('https://www.sitemaps.org/schemas/sitemap/0.9', 'urlset')->item(0);

        if (!$urlSet instanceof \DOMElement) {
            return $this;
        }

        $loc = $sitemap->createElementNS($urlSet->namespaceURI, 'loc');
        $loc->appendChild($sitemap->createTextNode($url));

        $urlEl = $sitemap->createElementNS($urlSet->namespaceURI, 'url');
        $urlEl->appendChild($loc);

        $urlSet->appendChild($urlEl);
        $sitemap->appendChild($urlSet);

        return $this;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getRootPageIds(): array
    {
        return $this->rootPageIds;
    }
}
