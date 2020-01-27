<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\PageType;

use Contao\PageRegular;

class RegularPageType extends AbstractSinglePageType implements HasLegacyPageInterface
{
    protected $features = [
        self::FEATURE_ARTICLES,
        self::FEATURE_ARTICLE_VIEW
    ];

    public function getLegacyPageClass(): string
    {
        return PageRegular::class;
    }
}
