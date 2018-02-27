<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\FaqBundle\Picker;

use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\CoreBundle\Framework\FrameworkAwareTrait;
use Contao\CoreBundle\Picker\AbstractPickerProvider;
use Contao\CoreBundle\Picker\DcaPickerProviderInterface;
use Contao\CoreBundle\Picker\PickerConfig;
use Contao\FaqCategoryModel;
use Contao\FaqModel;

/**
 * Provides the faq picker.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class FaqPickerProvider extends AbstractPickerProvider implements DcaPickerProviderInterface, FrameworkAwareInterface
{
    use FrameworkAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'faqPicker';
    }

    /**
     * {@inheritdoc}
     */
    public function supportsContext($context)
    {
        return 'link' === $context && $this->getUser()->hasAccess('faq', 'modules');
    }

    /**
     * {@inheritdoc}
     */
    public function supportsValue(PickerConfig $config)
    {
        return false !== strpos($config->getValue(), '{{faq_url::');
    }

    /**
     * {@inheritdoc}
     */
    public function getDcaTable()
    {
        return 'tl_faq';
    }

    /**
     * {@inheritdoc}
     */
    public function getDcaAttributes(PickerConfig $config)
    {
        $attributes = ['fieldType' => 'radio'];

        if ($this->supportsValue($config)) {
            $attributes['value'] = str_replace(['{{faq_url::', '}}'], '', $config->getValue());
        }

        return $attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function convertDcaValue(PickerConfig $config, $value)
    {
        return '{{faq_url::'.$value.'}}';
    }

    /**
     * {@inheritdoc}
     */
    protected function getRouteParameters(PickerConfig $config = null)
    {
        $params = ['do' => 'faq'];

        if (null === $config || !$config->getValue() || false === strpos($config->getValue(), '{{faq_url::')) {
            return $params;
        }

        $value = str_replace(['{{faq_url::', '}}'], '', $config->getValue());

        if (null !== ($faqId = $this->getFaqCategoryId($value))) {
            $params['table'] = 'tl_faq';
            $params['id'] = $faqId;
        }

        return $params;
    }

    /**
     * Returns the FAQ category ID.
     *
     * @param int $id
     *
     * @return int|null
     */
    private function getFaqCategoryId($id)
    {
        /** @var FaqModel $faqAdapter */
        $faqAdapter = $this->framework->getAdapter(FaqModel::class);

        if (!($faqModel = $faqAdapter->findById($id)) instanceof FaqModel) {
            return null;
        }

        if (!($faqCategory = $faqModel->getRelated('pid')) instanceof FaqCategoryModel) {
            return null;
        }

        return $faqCategory->id;
    }
}
