<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\DataContainer;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DcaExtractor;
use Contao\StringUtil;

class VirtualFieldsHandler
{
    public function __construct(private readonly ContaoFramework $contaoFramework)
    {
    }

    /**
     * This function takes the given record and checks for any virtual field storages
     * and expands their data. The JSON data of the storage will be automatically
     * decoded. The original data of the storages will be removed from the record.
     */
    public function expandFields(array $record, string $table): array
    {
        $dcaExtractor = $this->contaoFramework->createInstance(DcaExtractor::class, [$table]);

        $expanded = [];

        foreach ($dcaExtractor->getVirtualTargets() as $target) {
            if ($record[$target] ?? null) {
                if (\is_array($record[$target])) {
                    $decoded = $record[$target];
                } elseif (\is_string($record[$target])) {
                    try {
                        $decoded = json_decode($record[$target], true, flags: JSON_THROW_ON_ERROR);
                    } catch (\JsonException) {
                        // Ignore invalid JSON
                    }
                }

                if (isset($decoded) && \is_array($decoded)) {
                    $expanded = [...$expanded, ...$decoded];
                }
            }

            unset($record[$target]);
        }

        // Unset non-virtual fields
        $expanded = array_intersect_key($expanded, $dcaExtractor->getVirtualFields());

        return [...$record, ...$expanded];
    }

    /**
     * This function takes the given record and writes the data of virtual fields into
     * their respective storages. Note that the data of a storage will not be
     * automatically encoded to JSON as this would happen later on via DBAL.
     */
    public function combineFields(array $record, string $table): array
    {
        $dcaExtractor = $this->contaoFramework->createInstance(DcaExtractor::class, [$table]);

        $compressed = [];

        foreach ($dcaExtractor->getVirtualFields() as $virtualField => $storageField) {
            if (!\array_key_exists($virtualField, $record)) {
                continue;
            }

            $compressed[$storageField][$virtualField] = StringUtil::ensureStringUuids($record[$virtualField]);

            unset($record[$virtualField]);
        }

        return [...$record, ...$compressed];
    }
}
