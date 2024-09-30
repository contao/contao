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
     * The contao.backend_menu_build event is triggered when the back end menu is built.
     *
     * @see MenuEvent
     */
    public const BACKEND_MENU_BUILD = 'contao.backend_menu_build';

    /**
     * The contao.generate_symlinks event is triggered when the symlinks are generated.
     *
     * @see GenerateSymlinksEvent
     */
    public const GENERATE_SYMLINKS = 'contao.generate_symlinks';

    /**
     * The contao.image_sizes_all event is triggered when the image sizes are generated.
     *
     * @see ImageSizesEvent
     */
    public const IMAGE_SIZES_ALL = 'contao.image_sizes_all';

    /**
     * The contao.image_sizes_user event is triggered when the image sizes are
     * generated for a user.
     *
     * @see ImageSizesEvent
     */
    public const IMAGE_SIZES_USER = 'contao.image_sizes_user';

    /**
     * The contao.preview_url_create event is triggered when the front end preview URL
     * is generated.
     *
     * @see PreviewUrlCreateEvent
     */
    public const PREVIEW_URL_CREATE = 'contao.preview_url_create';

    /**
     * The contao.preview_url_convert event is triggered when the front end preview
     * URL is converted.
     *
     * @see PreviewUrlConvertEvent
     */
    public const PREVIEW_URL_CONVERT = 'contao.preview_url_convert';

    /**
     * The contao.robots_txt event is triggered when the /robots.txt route is called.
     *
     * @see RobotsTxtEvent
     */
    public const ROBOTS_TXT = 'contao.robots_txt';

    /**
     * The contao.sitemap event is triggered when the /sitemap.xml route is called.
     *
     * @see SitemapEvent
     */
    public const SITEMAP = 'contao.sitemap';

    /**
     * The contao.slug_valid_characters event is triggered when the valid slug
     * characters options are generated.
     *
     * @see SlugValidCharactersEvent
     */
    public const SLUG_VALID_CHARACTERS = 'contao.slug_valid_characters';
}
