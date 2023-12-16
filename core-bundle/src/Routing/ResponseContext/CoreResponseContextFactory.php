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

use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Contao\CoreBundle\Routing\ResponseContext\JsonLd\ContaoPageSchema;
use Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\String\HtmlDecoder;
use Contao\CoreBundle\Util\UrlUtil;
use Contao\PageModel;
use ParagonIE\CSPBuilder\CSPBuilder;
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
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly bool $cspReportingEnabled = false,
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

        if ($pageModel->enableCsp) {
            $csp = CSPBuilder::fromHeader(trim((string) $pageModel->csp));

            if ($this->cspReportingEnabled) {
                $urlContext = $this->urlGenerator->getContext();
                $baseUrl = $urlContext->getBaseUrl();
                $urlContext->setBaseUrl('');

                try {
                    $csp->setReportUri($this->urlGenerator->generate('contao_csp_reporter', [], UrlGeneratorInterface::ABSOLUTE_URL));
                } catch (RouteNotFoundException) {
                    // noop
                }

                $urlContext->setBaseUrl($baseUrl);
            }

            if ($pageModel->cspReportOnly) {
                $csp->setDirective('report-only');
            }

            if (!$pageModel->enableLegacyCsp) {
                $csp->disableOldBrowserSupport();
            }

            $context->add($csp);
        }

        return $context;
    }
}
