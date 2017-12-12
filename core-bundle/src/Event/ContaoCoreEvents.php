<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Event;

final class ContaoCoreEvents
{
    /**
     * The contao.backend_menu_build event is triggered when the backend menu is built.
     *
     * @var string
     *
     * @see MenuEvent
     */
    public const BACKEND_MENU_BUILD = 'contao.backend_menu_build';

    /**
     * The contao.image_sizes_all event is triggered when the image sizes are generated.
     *
     * @var string
     *
     * @see ImageSizesEvent
     */
    public const IMAGE_SIZES_ALL = 'contao.image_sizes_all';

    /**
     * The contao.image_sizes_user event is triggered when the image sizes are generated for a user.
     *
     * @var string
     *
     * @see ImageSizesEvent
     */
    public const IMAGE_SIZES_USER = 'contao.image_sizes_user';

    /**
     * The contao.preview_url_create event is triggered when the front end preview URL is generated.
     *
     * @var string
     *
     * @see PreviewUrlCreateEvent
     */
    public const PREVIEW_URL_CREATE = 'contao.preview_url_create';

    /**
     * The contao.preview_url_convert event is triggered when the front end preview URL is converted.
     *
     * @var string
     *
     * @see PreviewUrlConvertEvent
     */
    public const PREVIEW_URL_CONVERT = 'contao.preview_url_convert';

    /**
     * The contao.slug_valid_characters event is triggered when the valid slug characters options are generated.
     *
     * @var string
     *
     * @see SlugValidCharactersEvent
     */
    public const SLUG_VALID_CHARACTERS = 'contao.slug_valid_characters';
}
