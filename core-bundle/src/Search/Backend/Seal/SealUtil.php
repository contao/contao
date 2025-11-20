<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Search\Backend\Seal;

use CmsIg\Seal\Reindex\ReindexConfig as SealReindexConfig;
use Contao\CoreBundle\Search\Backend\Document;
use Contao\CoreBundle\Search\Backend\GroupedDocumentIds;
use Contao\CoreBundle\Search\Backend\ReindexConfig;

class SealUtil
{
    private const DOCUMENT_TYPE_IDENTIFIER_SEPARATOR = '__';

    public static function internalReindexConfigToSealReindexConfig(ReindexConfig $reindexConfig): SealReindexConfig
    {
        $sealConfig = (new SealReindexConfig())->withIndex(SealReindexProvider::getIndex());

        if ($reindexConfig->getUpdateSince()) {
            $sealConfig = $sealConfig->withDateTimeBoundary($reindexConfig->getUpdateSince());
        }

        if (!$reindexConfig->getLimitedDocumentIds()->isEmpty()) {
            $globalIdentifiers = [];

            foreach ($reindexConfig->getLimitedDocumentIds()->getTypes() as $type) {
                foreach ($reindexConfig->getLimitedDocumentIds()->getDocumentIdsForType($type) as $documentId) {
                    $globalIdentifiers[] = self::getGlobalDocumentId($type, $documentId);
                }
            }

            $sealConfig = $sealConfig->withIdentifiers($globalIdentifiers);
        }

        return $sealConfig;
    }

    public static function sealReindexConfigToInternalReindexConfig(SealReindexConfig $sealReindexConfig): ReindexConfig
    {
        $internalConfig = new ReindexConfig();

        if ($sealReindexConfig->getDateTimeBoundary()) {
            $internalConfig = $internalConfig->limitToDocumentsNewerThan($sealReindexConfig->getDateTimeBoundary());
        }

        if ([] !== $sealReindexConfig->getIdentifiers()) {
            $groupedDocumentIds = new GroupedDocumentIds();

            foreach ($sealReindexConfig->getIdentifiers() as $globalIdentifier) {
                [$type, $documentId] = explode(self::DOCUMENT_TYPE_IDENTIFIER_SEPARATOR, $globalIdentifier, 2);

                if ('' === $type || '' === $documentId) {
                    continue;
                }

                $groupedDocumentIds->addIdToType($type, $documentId);
            }

            $internalConfig = $internalConfig->limitToDocumentIds($groupedDocumentIds);
        }

        return $internalConfig;
    }

    public static function getGlobalDocumentId(string $type, string $id): string
    {
        // Ensure the ID is global across the search index by prefixing the id
        return $type.self::DOCUMENT_TYPE_IDENTIFIER_SEPARATOR.$id;
    }

    public static function convertProviderDocumentForSearchIndex(Document $document): array
    {
        return [
            'id' => self::getGlobalDocumentId($document->getType(), $document->getId()),
            'type' => $document->getType(),
            'searchableContent' => $document->getSearchableContent(),
            'tags' => $document->getTags(),
            'document' => json_encode($document->toArray(), JSON_THROW_ON_ERROR),
        ];
    }
}
