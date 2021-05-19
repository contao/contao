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

use Contao\CoreBundle\Event\JsonLdEvent;
use Contao\CoreBundle\Routing\ResponseContext\JsonLd\ContaoPageSchema;
use Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\PageModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class CoreResponseContextFactory implements EventSubscriberInterface
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

    public function createWebpageResponseContext(): WebpageResponseContext
    {
        $context = new WebpageResponseContext(new JsonLdManager($this->eventDispatcher));
        $this->responseContextAccessor->setResponseContext($context);

        return $context;
    }

    public function createResponseContext(): ResponseContext
    {
        $context = new ResponseContext();
        $this->responseContextAccessor->setResponseContext($context);

        return $context;
    }

    public function createContaoWebpageResponseContext(PageModel $pageModel): ContaoWebpageResponseContext
    {
        $context = new ContaoWebpageResponseContext(new JsonLdManager($this->eventDispatcher), $pageModel);
        $this->responseContextAccessor->setResponseContext($context);

        return $context;
    }

    public function onJsonLd(JsonLdEvent $event): void
    {
        $context = $this->responseContextAccessor->getResponseContext();

        if (!$context instanceof ContaoWebpageResponseContext) {
            return;
        }

        $event->getJsonLdManager()->getGraphForSchema(JsonLdManager::SCHEMA_CONTAO)->add(new ContaoPageSchema(
            $context->getTitle(),
            (int) $context->getPage()->id,
            !$context->isSearchable(),
            (bool) $context->getPage()->protected,
            array_map('intval', array_filter((array) $context->getPage()->groups)),
            $this->tokenChecker->isPreviewMode()
        ));
    }

    public static function getSubscribedEvents()
    {
        return [
            JsonLdEvent::class => ['onJsonLd'],
        ];
    }
}
