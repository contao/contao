<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Picker;

class ArticlePickerProvider extends AbstractPickerProvider implements DcaPickerProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'articlePicker';
    }

    /**
     * {@inheritdoc}
     */
    public function supportsContext($context): bool
    {
        return 'link' === $context && $this->getUser()->hasAccess('article', 'modules');
    }

    /**
     * {@inheritdoc}
     */
    public function supportsValue(PickerConfig $config): bool
    {
        return false !== strpos($config->getValue(), '{{article_url::');
    }

    /**
     * {@inheritdoc}
     */
    public function getDcaTable(): string
    {
        return 'tl_article';
    }

    /**
     * {@inheritdoc}
     */
    public function getDcaAttributes(PickerConfig $config): array
    {
        $attributes = ['fieldType' => 'radio'];

        if ($this->supportsValue($config)) {
            $attributes['value'] = str_replace(['{{article_url::', '}}'], '', $config->getValue());
        }

        return $attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function convertDcaValue(PickerConfig $config, $value): string
    {
        return '{{article_url::'.$value.'}}';
    }

    /**
     * {@inheritdoc}
     */
    protected function getRouteParameters(PickerConfig $config = null): array
    {
        return ['do' => 'article'];
    }
}
