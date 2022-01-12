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
use Contao\CoreBundle\Image\ImageSizes;
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Symfony\Component\Security\Core\Security;

/**
 * @Callback(table="tl_layout", target="fields.lightboxSize.options")
 * @Callback(table="tl_content", target="fields.size.options")
 * @Callback(table="tl_module", target="fields.imgSize.options")
 */
class ImageSizeOptionsListener
{
    private Security $security;
    private ImageSizes $imageSizes;

    public function __construct(Security $security, ImageSizes $imageSizes)
    {
        $this->security = $security;
        $this->imageSizes = $imageSizes;
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
