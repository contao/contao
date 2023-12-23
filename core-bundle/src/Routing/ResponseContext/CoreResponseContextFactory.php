<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing\ResponseContext;

use Contao\CoreBundle\Controller\CspReporterController;
use Contao\CoreBundle\Csp\CspParser;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\Routing\ResponseContext\Csp\CspHandler;
use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Contao\CoreBundle\Routing\ResponseContext\JsonLd\ContaoPageSchema;
use Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\String\HtmlDecoder;
use Contao\CoreBundle\Util\UrlUtil;
use Contao\PageModel;
use Spatie\SchemaOrg\WebPage;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class CoreResponseContextFactory
{
    public function __construct(
        private readonly ResponseContextAccessor $responseContextAccessor,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly TokenChecker $tokenChecker,
        private readonly HtmlDecoder $htmlDecoder,
        private readonly RequestStack $requestStack,
        private readonly InsertTagParser $insertTagParser,
        private readonly CspParser $cspParser,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function createResponseContext(): ResponseContext
    {
        $context = new ResponseContext();

        $this->responseContextAccessor->setResponseContext($context);

        return $context;
    }

    public function createWebpageResponseContext(): ResponseContext
    {
        $context = $this->createResponseContext();
        $context->add($this->eventDispatcher);
        $context->addLazy(HtmlHeadBag::class);

        $context->addLazy(
            JsonLdManager::class,
            static function () use ($context) {
                $manager = new JsonLdManager($context);
                $manager->getGraphForSchema(JsonLdManager::SCHEMA_ORG)->add(new WebPage());

                return $manager;
            },
        );

        return $context;
    }

    public function createContaoWebpageResponseContext(PageModel $pageModel): ResponseContext
    {
        $context = $this->createWebpageResponseContext();
        $htmlHeadBag = $context->get(HtmlHeadBag::class);
        $jsonLdManager = $context->get(JsonLdManager::class);

        $title = $this->htmlDecoder->inputEncodedToPlainText($pageModel->pageTitle ?: $pageModel->title ?: '');

        $htmlHeadBag
            ->setTitle($title ?: '')
            ->setMetaDescription($this->htmlDecoder->inputEncodedToPlainText($pageModel->description ?: ''))
        ;

        if ($pageModel->robots) {
            $htmlHeadBag->setMetaRobots($pageModel->robots);
        }

        if ($pageModel->enableCanonical && $pageModel->canonicalLink) {
            $url = $this->insertTagParser->replaceInline($pageModel->canonicalLink);

            // Ensure absolute links
            if (!preg_match('#^https?://#', $url)) {
                if (!$request = $this->requestStack->getCurrentRequest()) {
                    throw new \RuntimeException('The request stack did not contain a request');
                }

                $url = UrlUtil::makeAbsolute($url, $request->getUri());
            }

            $htmlHeadBag->setCanonicalUri($url);
        }

        if ($pageModel->enableCanonical && $pageModel->canonicalKeepParams) {
            $htmlHeadBag->setKeepParamsForCanonical(array_map('trim', explode(',', $pageModel->canonicalKeepParams)));
        }

        $jsonLdManager
            ->getGraphForSchema(JsonLdManager::SCHEMA_CONTAO)
            ->set(
                new ContaoPageSchema(
                    $title ?: '',
                    $pageModel->id,
                    $pageModel->noSearch,
                    $pageModel->protected,
                    array_map('intval', array_filter((array) $pageModel->groups)),
                    $this->tokenChecker->isPreviewMode(),
                ),
            )
        ;

        $this->addCspHandler($context, $pageModel);

        return $context;
    }

    private function addCspHandler(ResponseContext $context, PageModel $pageModel): void
    {
        if (!$pageModel->enableCsp) {
            return;
        }

        $directives = $this->cspParser->parseHeader(trim((string) $pageModel->csp));
        $directives->setLevel1Fallback(false);

        if ($pageModel->cspReportLog) {
            $urlContext = $this->urlGenerator->getContext();
            $baseUrl = $urlContext->getBaseUrl();

            // Remove preview script if present
            $urlContext->setBaseUrl('');

            try {
                $reportUri = $this->urlGenerator->generate(CspReporterController::class, ['page' => $pageModel->id], UrlGeneratorInterface::ABSOLUTE_URL);
                $directives->setDirective('report-uri', $reportUri);
            } catch (RouteNotFoundException) {
                // noop
            } finally {
                $urlContext->setBaseUrl($baseUrl);
            }
        }

        $cspHandler = new CspHandler($directives, (bool) $pageModel->cspReportOnly);

        $context->add($cspHandler);
    }
}
