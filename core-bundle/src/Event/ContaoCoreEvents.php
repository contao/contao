<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Event;

/**
 * Defines constants for the Contao events.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 * @author Andreas Schempp <https://github.com/aschempp>
 */
final class ContaoCoreEvents
{
    /**
     * The contao.image_sizes_all event is triggered when the image sizes are generated.
     *
     * @var string
     *
     * @see ImageSizesEvent
     */
    const IMAGE_SIZES_ALL = 'contao.image_sizes_all';

    /**
     * The contao.image_sizes_user event is triggered when the image sizes are generated for a user.
     *
     * @var string
     *
     * @see ImageSizesEvent
     */
    const IMAGE_SIZES_USER = 'contao.image_sizes_user';

    /**
     * The contao.preview_url_create event is triggered when the front end preview URL is generated.
     *
     * @var string
     *
     * @see PreviewUrlCreateEvent
     */
    const PREVIEW_URL_CREATE = 'contao.preview_url_create';

    /**
     * The contao.preview_url_convert event is triggered when the front end preview URL is converted.
     *
     * @var string
     *
     * @see PreviewUrlConvertEvent
     */
    const PREVIEW_URL_CONVERT = 'contao.preview_url_convert';
}
