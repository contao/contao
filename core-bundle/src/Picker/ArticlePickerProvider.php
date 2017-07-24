<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Picker;

/**
 * Provides the article picker.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class ArticlePickerProvider extends AbstractPickerProvider implements DcaPickerProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'articlePicker';
    }

    /**
     * {@inheritdoc}
     */
    public function supportsContext($context)
    {
        return 'link' === $context && $this->getUser()->hasAccess('article', 'modules');
    }

    /**
     * {@inheritdoc}
     */
    public function supportsValue(PickerConfig $config)
    {
        return false !== strpos($config->getValue(), '{{article_url::');
    }

    /**
     * {@inheritdoc}
     */
    public function getDcaTable()
    {
        return 'tl_article';
    }

    /**
     * {@inheritdoc}
     */
    public function getDcaAttributes(PickerConfig $config)
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
    public function convertDcaValue(PickerConfig $config, $value)
    {
        return '{{article_url::'.$value.'}}';
    }

    /**
     * {@inheritdoc}
     */
    protected function getRouteParameters(PickerConfig $config)
    {
        return ['do' => 'article'];
    }
}
