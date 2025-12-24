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

class VirtualFieldHandler
{
    public function __construct(private readonly ContaoFramework $contaoFramework)
    {
    }

    public function expandFields(array $record, string $table): array
    {
        $dcaExtractor = $this->contaoFramework->createInstance(DcaExtractor::class, [$table]);

        $expanded = [];

        foreach ($dcaExtractor->getVirtualTargets() as $target) {
            if ($record[$target] ?? null) {
                try {
                    $expanded = [...$expanded, ...json_decode($record[$target], true, flags: JSON_THROW_ON_ERROR)];
                } catch (\JsonException) {
                    // Ignore invalid JSON
                }
            }

            unset($record[$target]);
        }

        // Unset non-virtual fields
        $expanded = array_intersect_key($expanded, $dcaExtractor->getVirtualFields());

        return [...$record, ...$expanded];
    }
}
