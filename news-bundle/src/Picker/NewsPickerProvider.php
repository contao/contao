<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\NewsBundle\Picker;

use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\CoreBundle\Framework\FrameworkAwareTrait;
use Contao\CoreBundle\Picker\AbstractPickerProvider;
use Contao\CoreBundle\Picker\DcaPickerProviderInterface;
use Contao\CoreBundle\Picker\PickerConfig;
use Contao\NewsArchiveModel;
use Contao\NewsModel;

/**
 * Provides the news picker.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class NewsPickerProvider extends AbstractPickerProvider implements DcaPickerProviderInterface, FrameworkAwareInterface
{
    use FrameworkAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'newsPicker';
    }

    /**
     * {@inheritdoc}
     */
    public function supportsContext($context)
    {
        return 'link' === $context && $this->getUser()->hasAccess('news', 'modules');
    }

    /**
     * {@inheritdoc}
     */
    public function supportsValue(PickerConfig $config)
    {
        return false !== strpos($config->getValue(), '{{news_url::');
    }

    /**
     * {@inheritdoc}
     */
    public function getDcaTable()
    {
        return 'tl_news';
    }

    /**
     * {@inheritdoc}
     */
    public function getDcaAttributes(PickerConfig $config)
    {
        $attributes = ['fieldType' => 'radio'];

        if ($this->supportsValue($config)) {
            $attributes['value'] = str_replace(['{{news_url::', '}}'], '', $config->getValue());
        }

        return $attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function convertDcaValue(PickerConfig $config, $value)
    {
        return '{{news_url::'.$value.'}}';
    }

    /**
     * {@inheritdoc}
     */
    protected function getRouteParameters(PickerConfig $config = null)
    {
        $params = ['do' => 'news'];

        if (null === $config || !$config->getValue() || false === strpos($config->getValue(), '{{news_url::')) {
            return $params;
        }

        $value = str_replace(['{{news_url::', '}}'], '', $config->getValue());

        if (null !== ($newsArchiveId = $this->getNewsArchiveId($value))) {
            $params['table'] = 'tl_news';
            $params['id'] = $newsArchiveId;
        }

        return $params;
    }

    /**
     * Returns the news archive ID.
     *
     * @param int $id
     *
     * @return int|null
     */
    private function getNewsArchiveId($id)
    {
        /** @var NewsModel $newsAdapter */
        $newsAdapter = $this->framework->getAdapter(NewsModel::class);

        if (!($newsModel = $newsAdapter->findById($id)) instanceof NewsModel) {
            return null;
        }

        if (!($newsArchive = $newsModel->getRelated('pid')) instanceof NewsArchiveModel) {
            return null;
        }

        return $newsArchive->id;
    }
}
