<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing\ResponseContext\Csp;

use Contao\CoreBundle\Controller\CspReporterController;
use Contao\CoreBundle\Csp\CspParser;
use Contao\PageModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CspHandlerFactory
{
    public function __construct(
        private readonly CspParser $cspParser,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly int $maxHeaderLength = 3072,
        private readonly LoggerInterface|null $logger = null,
    ) {
    }

    /**
     * @param string|null $csp Existing CSP header if any
     */
    public function create(string|null $csp = null): CspHandler
    {
        return new CspHandler($this->cspParser->parseHeader($csp), $this->maxHeaderLength, $this->logger);
    }

    public function createFromPage(PageModel $page): CspHandler|null
    {
        if (!$page->enableCsp) {
            return null;
        }

        $cspHandler = $this->create((string) $page->csp);
        $cspHandler->getDirectives()->setLevel1Fallback(false);
        $cspHandler->setReportOnly($page->cspReportOnly);

        if ($page->cspReportLog) {
            $urlContext = $this->urlGenerator->getContext();
            $baseUrl = $urlContext->getBaseUrl();

            // Remove preview script if present
            $urlContext->setBaseUrl('');

            try {
                $reportUri = $this->urlGenerator->generate(CspReporterController::class, ['page' => $page->id], UrlGeneratorInterface::ABSOLUTE_URL);
                $cspHandler->getDirectives()->setDirective('report-uri', $reportUri);
            } catch (RouteNotFoundException) {
                // noop
            } finally {
                $urlContext->setBaseUrl($baseUrl);
            }
        }

        return $cspHandler;
    }
}
