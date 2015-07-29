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
 */
final class ContaoCoreEvents
{
    /**
     * The contao.get_image_sizes event is triggered when image sizes are generated.
     *
     * @var string
     *
     * @see Contao\CoreBundle\Event\GetImageSizesEvent
     */
    const GET_IMAGE_SIZES = 'contao.get_image_sizes';
}