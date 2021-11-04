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

use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Contao\CoreBundle\Routing\ResponseContext\JsonLd\ContaoPageSchema;
use Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\String\HtmlDecoder;
use Contao\Environment;
use Contao\PageModel;
use Spatie\SchemaOrg\WebPage;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class CoreResponseContextFactory
{
    private ResponseContextAccessor $responseContextAccessor;
    private EventDispatcherInterface $eventDispatcher;
    private TokenChecker $tokenChecker;
    private ContaoFramework $contaoFramework;
    private HtmlDecoder $htmlDecoder;

    public function __construct(ResponseContextAccessor $responseContextAccessor, EventDispatcherInterface $eventDispatcher, TokenChecker $tokenChecker, ContaoFramework $contaoFramework, HtmlDecoder $htmlDecoder)
    {
        $this->responseContextAccessor = $responseContextAccessor;
        $this->eventDispatcher = $eventDispatcher;
        $this->tokenChecker = $tokenChecker;
        $this->contaoFramework = $contaoFramework;
        $this->htmlDecoder = $htmlDecoder;
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
            }
        );

        return $context;
    }

    public function createContaoWebpageResponseContext(PageModel $pageModel): ResponseContext
    {
        $context = $this->createWebpageResponseContext();

        /** @var HtmlHeadBag $htmlHeadBag */
        $htmlHeadBag = $context->get(HtmlHeadBag::class);

        /** @var JsonLdManager $jsonLdManager */
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
            $url = $this->contaoFramework->getAdapter(Controller::class)->replaceInsertTags($pageModel->canonicalLink, false);

            // Ensure absolute links (FIXME: Remove once we remove support for relative urls)
            if (!preg_match('#^https?://#', $url)) {
                $url = $this->contaoFramework->getAdapter(Environment::class)->get('base').$url;
            }
            $htmlHeadBag->setCanonicalUri($url);
        }

        if ($pageModel->enableCanonical && $pageModel->canonicalKeepParams) {
            $htmlHeadBag->setKeepParamsForCanonical(array_map('trim', explode(',', (string) $pageModel->canonicalKeepParams)));
        }

        $jsonLdManager
            ->getGraphForSchema(JsonLdManager::SCHEMA_CONTAO)
            ->set(
                new ContaoPageSchema(
                    $title ?: '',
                    (int) $pageModel->id,
                    (bool) $pageModel->noSearch,
                    (bool) $pageModel->protected,
                    array_map('intval', array_filter((array) $pageModel->groups)),
                    $this->tokenChecker->isPreviewMode()
                )
            )
        ;

        return $context;
    }
}
