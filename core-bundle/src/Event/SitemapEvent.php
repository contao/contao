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
    private \DOMDocument $document;
    private Request $request;
    private array $rootPageIds;

    public function __construct(\DOMDocument $document, Request $request, array $rootPageIds)
    {
        $this->document = $document;
        $this->request = $request;
        $this->rootPageIds = $rootPageIds;
    }

    public function getDocument(): \DOMDocument
    {
        return $this->document;
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
