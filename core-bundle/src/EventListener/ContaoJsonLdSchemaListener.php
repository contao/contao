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
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Updates the schema.contao.org schema before rendering it with the current data
 * from the HtmlHeadBag.
 */
#[AsEventListener]
class ContaoJsonLdSchemaListener
{
    public function __invoke(JsonLdEvent $event): void
    {
        $responseContext = $event->getResponseContext();

        if (!$responseContext->has(HtmlHeadBag::class) || !$responseContext->has(JsonLdManager::class)) {
            return;
        }

        $jsonLdManager = $responseContext->get(JsonLdManager::class);

        if (!$jsonLdManager->getGraphForSchema(JsonLdManager::SCHEMA_CONTAO)->has(ContaoPageSchema::class)) {
            return;
        }

        $htmlHeadBag = $responseContext->get(HtmlHeadBag::class);

        $schema = $jsonLdManager->getGraphForSchema(JsonLdManager::SCHEMA_CONTAO)->get(ContaoPageSchema::class);
        $schema->updateFromHtmlHeadBag($htmlHeadBag);
    }
}
