<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Event;

/**
 * Defines Constants for all Contao events.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 * @author Andreas Schempp <https://github.com/aschempp>
 */
final class ContaoCoreEvents
{
    /**
     * The contao.image_sizes_all event is triggered when all image sizes are generated.
     *
     * @var string
     *
     * @see Contao\CoreBundle\Event\ImageSizesEvent
     */
    const IMAGE_SIZES_ALL = 'contao.image_sizes_all';

    /**
     * The contao.image_sizes_user event is triggered when image sizes are generated for a user.
     *
     * @var string
     *
     * @see Contao\CoreBundle\Event\ImageSizesEvent
     */
    const IMAGE_SIZES_USER = 'contao.image_sizes_user';
}
