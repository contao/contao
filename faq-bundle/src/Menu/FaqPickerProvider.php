<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\FaqBundle\Menu;

use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\CoreBundle\Framework\FrameworkAwareTrait;
use Contao\CoreBundle\Menu\AbstractMenuProvider;
use Contao\CoreBundle\Menu\PickerMenuProviderInterface;
use Contao\FaqCategoryModel;
use Contao\FaqModel;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the FAQ picker.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class FaqPickerProvider extends AbstractMenuProvider implements PickerMenuProviderInterface, FrameworkAwareInterface
{
    use FrameworkAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function supports($context)
    {
        return 'link' === $context;
    }

    /**
     * {@inheritdoc}
     */
    public function createMenu(ItemInterface $menu, FactoryInterface $factory)
    {
        $user = $this->getUser();

        if ($user->hasAccess('faq', 'modules')) {
            $this->addMenuItem($menu, $factory, 'faq', 'faqPicker', 'faq');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTable($table)
    {
        return 'tl_faq' === $table;
    }

    /**
     * {@inheritdoc}
     */
    public function processSelection($value)
    {
        return sprintf('{{faq_url::%s}}', $value);
    }

    /**
     * {@inheritdoc}
     */
    public function canHandle(Request $request)
    {
        return $request->query->has('value') && false !== strpos($request->query->get('value'), '{{faq_url::');
    }

    /**
     * {@inheritdoc}
     */
    public function getPickerUrl(Request $request)
    {
        $params = $request->query->all();
        $params['do'] = 'faq';
        $params['value'] = str_replace(['{{faq_url::', '}}'], '', $params['value']);

        if (null !== ($newsArchiveId = $this->getFaqCategoryId($params['value']))) {
            $params['table'] = 'tl_faq';
            $params['id'] = $newsArchiveId;
        }

        return $this->route('contao_backend', $params);
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

        if (!(($faqModel = $faqAdapter->findById($id)) instanceof FaqModel)) {
            return null;
        }

        if (!(($faqCategory = $faqModel->getRelated('pid')) instanceof FaqCategoryModel)) {
            return null;
        }

        return $faqCategory->id;
    }
}
