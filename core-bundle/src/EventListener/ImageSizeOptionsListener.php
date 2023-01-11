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

use Contao\BackendUser;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Image\ImageSizes;
use Symfony\Component\Security\Core\Security;

#[AsCallback(table: 'tl_layout', target: 'fields.lightboxSize.options')]
#[AsCallback(table: 'tl_content', target: 'fields.size.options')]
#[AsCallback(table: 'tl_module', target: 'fields.imgSize.options')]
class ImageSizeOptionsListener
{
    public function __construct(private Security $security, private ImageSizes $imageSizes)
    {
    }

    public function __invoke(): array
    {
        $user = $this->security->getUser();

        if (!$user instanceof BackendUser) {
            return [];
        }

        return $this->imageSizes->getOptionsForUser($user);
    }
}
