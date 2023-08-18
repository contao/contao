<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Runtime;

use Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContext;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContextAccessor;
use Spatie\SchemaOrg\Graph;
use Twig\Extension\RuntimeExtensionInterface;

final class SchemaOrgRuntime implements RuntimeExtensionInterface
{
    /**
     * @internal
     */
    public function __construct(private readonly ResponseContextAccessor $responseContextAccessor)
    {
    }

    /**
     * Adds schema.org JSON-LD data to the current response context.
     */
    public function add(array $jsonLd): void
    {
        $responseContext = $this->responseContextAccessor->getResponseContext();

        if (!$responseContext instanceof ResponseContext || !$responseContext->has(JsonLdManager::class)) {
            return;
        }

        /** @var JsonLdManager $jsonLdManager */
        $jsonLdManager = $responseContext->get(JsonLdManager::class);
        $type = $jsonLdManager->createSchemaOrgTypeFromArray($jsonLd);

        $jsonLdManager
            ->getGraphForSchema(JsonLdManager::SCHEMA_ORG)
            ->set($type, $jsonLd['identifier'] ?? Graph::IDENTIFIER_DEFAULT)
        ;
    }
}
