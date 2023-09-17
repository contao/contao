<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener;

use Contao\CoreBundle\Event\JsonLdEvent;
use Contao\CoreBundle\EventListener\ContaoJsonLdSchemaListener;
use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Contao\CoreBundle\Routing\ResponseContext\JsonLd\ContaoPageSchema;
use Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContext;
use PHPUnit\Framework\TestCase;

class ContaoJsonLdSchemaListenerTest extends TestCase
{
    public function testUpdatesTheTitleFromHtmlHeadBag(): void
    {
        $headTagBag = new HtmlHeadBag();
        $headTagBag->setTitle('Title different');

        $context = new ResponseContext();
        $context->add($headTagBag);

        $schema = new ContaoPageSchema('Original title', 42, false, false, [], false);

        $jsonLdManager = new JsonLdManager($context);
        $jsonLdManager->getGraphForSchema(JsonLdManager::SCHEMA_CONTAO)->set($schema);

        $context->add($jsonLdManager);

        $event = new JsonLdEvent();
        $event->setResponseContext($context);

        $listener = new ContaoJsonLdSchemaListener();
        $listener($event);

        $jsonLdManager = $context->get(JsonLdManager::class);

        /** @var ContaoPageSchema $schema */
        $schema = $jsonLdManager->getGraphForSchema(JsonLdManager::SCHEMA_CONTAO)->get(ContaoPageSchema::class);

        $this->assertSame('Title different', $schema->getTitle());
    }
}
