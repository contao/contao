<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Image\Studio;

use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;

class MetaDataFactory
{
    /**
     * @var ContaoFramework
     */
    private $framework;

    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    /**
     * Create a meta data container from a set of values.
     *
     * @param bool $canonicalize Whether the values should be constrained to the current `tl_files.meta` configuration.
     */
    public function create(array $values, bool $canonicalize = true): MetaData
    {
        if ($canonicalize) {
            $values = $this->canonicalizeValues($values, $this->getMetaFields());
        }

        return new MetaData($values);
    }

    /**
     * Create an empty meta data container that will contain the default fields
     * with empty values.
     */
    public function createEmpty(): MetaData
    {
        return $this->create([]);
    }

    /**
     * Get the meta fields registered in `tl_files.meta`.
     */
    public function getMetaFields(): array
    {
        $this->framework->initialize();

        /** @var Controller $controller */
        $controller = $this->framework->getAdapter(Controller::class);
        $controller->loadDataContainer('tl_files');

        return array_keys($GLOBALS['TL_DCA']['tl_files']['fields']['meta']['eval']['metaFields'] ?? []);
    }

    /**
     * Remove value pairs and insert empty placeholders (''), so that the
     * result set contains only, but all of `$allowedFields`.
     */
    private function canonicalizeValues(array $values, array $allowedFields): array
    {
        // Strip superfluous
        $values = array_intersect_key($values, array_flip($allowedFields));

        // Fill possibly missing fields with empty values
        return array_merge(
            array_combine($allowedFields, array_fill(0, \count($allowedFields), '')),
            $values
        );
    }
}
