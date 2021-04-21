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

use Contao\CoreBundle\Routing\Page\Metadata\PageMetadataContainer;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class AddJsonLdListener
{
    /**
     * @var ScopeMatcher
     */
    private $scopeMatcher;

    /**
     * @var PageMetadataContainer
     */
    private $pageMetadataContainer;

    public function __construct(ScopeMatcher $scopeMatcher, PageMetadataContainer $pageMetadataContainer)
    {
        $this->scopeMatcher = $scopeMatcher;
        $this->pageMetadataContainer = $pageMetadataContainer;
    }

    public function __invoke(ResponseEvent $event): void
    {
        if (!$this->scopeMatcher->isFrontendMasterRequest($event)) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        // Do not capture redirects, errors, or modify XML HTTP Requests
        if (
            $request->isXmlHttpRequest()
            || !($response->isSuccessful() || $response->isClientError())
        ) {
            return;
        }

        // Only inject into HTML responses
        if (
            'html' !== $request->getRequestFormat()
            || false === strpos((string) $response->headers->get('Content-Type'), 'text/html')
            || false !== stripos((string) $response->headers->get('Content-Disposition'), 'attachment;')
        ) {
            return;
        }

        $this->injectJsonLd($response);
    }

    private function injectJsonLd(Response $response): void
    {
        $content = $response->getContent();
        $pos = strripos($content, '</body>');

        if (false === $pos) {
            return;
        }

        // TODO: should we dispatch another event here so everybody can still add JSON-LD data one final time
        // before it's added to the HTML. Maybe to e.g. go through all the ImageObject schemas and extend them
        // with more custom attributes coming from elsewhere.

        $data = [];

        foreach ($this->pageMetadataContainer->getJsonLdManager()->getGraphs() as $graph) {
            $data[] = $graph->toArray();
        }

        if (0 === \count($data)) {
            return;
        }

        $jsonLd = '<script type="application/ld+json">'."\n".json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n".'</script>';

        $response->setContent(substr($content, 0, $pos)."\n".$jsonLd."\n".substr($content, $pos));
    }
}
