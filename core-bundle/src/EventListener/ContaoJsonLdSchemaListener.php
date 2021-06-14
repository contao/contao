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

use Contao\CoreBundle\Event\JsonLdEvent;
use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Contao\CoreBundle\Routing\ResponseContext\JsonLd\ContaoPageSchema;
use Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContextAccessor;

/**
 * Updates the schema.contao.org schema before rendering it
 * with the current data from the HtmlHeadBag.
 */
class ContaoJsonLdSchemaListener
{
    /**
     * @var ResponseContextAccessor
     */
    private $responseContextAccessor;

    public function __construct(ResponseContextAccessor $responseContextAccessor)
    {
        $this->responseContextAccessor = $responseContextAccessor;
    }

    public function __invoke(JsonLdEvent $event): void
    {
        $responseContext = $this->responseContextAccessor->getResponseContext();

        if (!$responseContext->has(HtmlHeadBag::class)) {
            return;
        }

        if (!$event->getJsonLdManager()->getGraphForSchema(JsonLdManager::SCHEMA_CONTAO)->has(ContaoPageSchema::class)) {
            return;
        }

        /** @var HtmlHeadBag $htmlHeadBag */
        $htmlHeadBag = $responseContext->get(HtmlHeadBag::class);

        /** @var ContaoPageSchema $schema */
        $schema = $event->getJsonLdManager()->getGraphForSchema(JsonLdManager::SCHEMA_CONTAO)->get(ContaoPageSchema::class);

        $schema->updateFromHtmlHeadBag($htmlHeadBag);
    }
}
