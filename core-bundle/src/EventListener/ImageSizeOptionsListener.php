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

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Image\ImageSizes;

#[AsCallback(table: 'tl_layout', target: 'fields.lightboxSize.options')]
#[AsCallback(table: 'tl_content', target: 'fields.size.options')]
#[AsCallback(table: 'tl_module', target: 'fields.imgSize.options')]
#[AsCallback(table: 'tl_page', target: 'fields.imgSize.options')]
class ImageSizeOptionsListener
{
    public function __construct(private readonly ImageSizes $imageSizes)
    {
    }

    public function __invoke(): array
    {
        return $this->imageSizes->getOptionsForUser();
    }
}
