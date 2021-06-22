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

use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Contao\CoreBundle\Routing\ResponseContext\JsonLd\ContaoPageSchema;
use Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\PageModel;
use Contao\StringUtil;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class CoreResponseContextFactory
{
    /**
     * @var ResponseContextAccessor
     */
    private $responseContextAccessor;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var TokenChecker
     */
    private $tokenChecker;

    public function __construct(ResponseContextAccessor $responseContextAccessor, EventDispatcherInterface $eventDispatcher, TokenChecker $tokenChecker)
    {
        $this->responseContextAccessor = $responseContextAccessor;
        $this->eventDispatcher = $eventDispatcher;
        $this->tokenChecker = $tokenChecker;
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
        $context->addLazy(JsonLdManager::class);

        return $context;
    }

    public function createContaoWebpageResponseContext(PageModel $pageModel): ResponseContext
    {
        $context = $this->createWebpageResponseContext();

        /** @var HtmlHeadBag $htmlHeadBag */
        $htmlHeadBag = $context->get(HtmlHeadBag::class);

        /** @var JsonLdManager $jsonLdManager */
        $jsonLdManager = $context->get(JsonLdManager::class);

        $title = $pageModel->pageTitle ?: StringUtil::inputEncodedToPlainText($pageModel->title ?: '');

        $htmlHeadBag
            ->setTitle($title ?: '')
            ->setMetaDescription(StringUtil::inputEncodedToPlainText($pageModel->description ?: ''))
        ;

        if ($pageModel->robots) {
            $htmlHeadBag->setMetaRobots($pageModel->robots);
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
