<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Event;

final class ContaoCoreEvents
{
    /**
     * The contao.image_sizes_all event is triggered when the image sizes are generated.
     *
     * @see ImageSizesEvent
     */
    public const IMAGE_SIZES_ALL = 'contao.image_sizes_all';

    /**
     * The contao.image_sizes_user event is triggered when the image sizes are generated for a user.
     *
     * @see ImageSizesEvent
     */
    public const IMAGE_SIZES_USER = 'contao.image_sizes_user';
}
