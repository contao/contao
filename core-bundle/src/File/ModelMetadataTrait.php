<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\File;

use Contao\FilesModel;
use Contao\System;
use Contao\Validator;

/**
 * @property string $overwriteMeta
 *
 * @method array row()
 */
trait ModelMetadataTrait
{
    /**
     * Get the default metadata or null if not applicable.
     */
    public function getOverwriteMetadata(): Metadata|null
    {
        // Ignore if "overwriteMeta" is not set
        if (!$this->overwriteMeta) {
            return null;
        }

        $data = $this->row();

        // Normalize keys
        if (isset($data['imageTitle'])) {
            $data[Metadata::VALUE_TITLE] = $data['imageTitle'];
        }

        if (isset($data['imageUrl'])) {
            $url = $data['imageUrl'];

            if (Validator::isRelativeUrl($url)) {
                $url = System::getContainer()->get('contao.assets.files_context')->getStaticUrl().$url;
            }

            $data[Metadata::VALUE_URL] = $url;
        }

        unset($data['imageTitle'], $data['imageUrl']);

        // Make sure we resolve insert tags pointing to files
        if (isset($data[Metadata::VALUE_URL])) {
            $data[Metadata::VALUE_URL] = System::getContainer()->get('contao.insert_tag.parser')->replaceInline($data[Metadata::VALUE_URL] ?? '');
        }

        // Strip superfluous fields by intersecting with tl_files.meta.eval.metaFields
        return new Metadata(array_intersect_key($data, array_flip(FilesModel::getMetaFields())));
    }
}
