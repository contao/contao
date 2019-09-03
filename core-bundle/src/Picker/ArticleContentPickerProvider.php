<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Picker;

class ArticleContentPickerProvider extends AbstractContentPickerProvider
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'articleContentPicker';
    }

    /**
     * {@inheritdoc}
     */
    protected function getBackendModule(): string
    {
        return 'article';
    }

    /**
     * {@inheritdoc}
     */
    protected function getParentTable(): string
    {
        return 'tl_article';
    }
}
