<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\Crawl\Escargot\Factory;
use Contao\CoreBundle\Messenger\Message\SearchIndexMessage;
use Contao\CoreBundle\Search\Document;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @internal
 */
class SearchIndexListener
{
    final public const FEATURE_INDEX = 0b01;
    final public const FEATURE_DELETE = 0b10;

    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly string $fragmentPath = '_fragment',
        private readonly int $enabledFeatures = self::FEATURE_INDEX | self::FEATURE_DELETE,
    ) {
    }

    /**
     * Checks if the request can be indexed and forwards it accordingly.
     */
    public function __invoke(TerminateEvent $event): void
    {
        $response = $event->getResponse();

        if ($response->isRedirection()) {
            return;
        }

        $request = $event->getRequest();
        $document = Document::createFromRequestResponse($request, $response);
        $needsIndex = $this->needsIndex($request, $response, $document);
        $success = $event->getResponse()->isSuccessful();

        if ($needsIndex && $success && $this->enabledFeatures & self::FEATURE_INDEX) {
            $this->messageBus->dispatch(SearchIndexMessage::createWithIndex($document));
        }

        if (!$success && $this->enabledFeatures & self::FEATURE_DELETE) {
            $this->messageBus->dispatch(SearchIndexMessage::createWithDelete($document));
        }
    }

    private function needsIndex(Request $request, Response $response, Document $document): bool
    {
        // Only handle GET requests (see #1194)
        if (!$request->isMethod(Request::METHOD_GET)) {
            return false;
        }

        // Do not index if called by crawler
        if (Factory::USER_AGENT === $request->headers->get('User-Agent')) {
            return false;
        }

        // Do not handle fragments
        if (preg_match('~(?:^|/)'.preg_quote($this->fragmentPath, '~').'/~', $request->getPathInfo())) {
            return false;
        }

        // Do not index if the X-Robots-Tag header contains "noindex"
        if (str_contains((string) $response->headers->get('X-Robots-Tag', ''), 'noindex')) {
            return false;
        }

        try {
            $robots = $document->getContentCrawler()->filterXPath('//head/meta[@name="robots"]')->first()->attr('content');

            // Do not index if the meta robots tag contains "noindex"
            if (str_contains((string) $robots, 'noindex')) {
                return false;
            }
        } catch (\Exception) {
            // No meta robots tag found
        }

        // If there are no json ld scripts at all, this should not be handled by our indexer
        if (!$document->extractJsonLdScripts()) {
            return false;
        }

        return true;
    }
}
