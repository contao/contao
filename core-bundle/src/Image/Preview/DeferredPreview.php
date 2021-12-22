<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Image\Preview;

use Contao\Image\Image;
use Contao\Image\ImageDimensions;

class DeferredPreview extends Image
{
    /**
     * No parent::__construct() call here, as we overwrite the parent
     * constructor to skip the file_exists() checks.
     */
    public function __construct(string $path, ImageDimensions $dimensions)
    {
        $this->path = $path;
        $this->dimensions = $dimensions;
    }
}
