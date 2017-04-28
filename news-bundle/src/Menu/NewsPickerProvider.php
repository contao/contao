<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\NewsBundle\Menu;

use Contao\CoreBundle\Framework\FrameworkAwareTrait;
use Contao\CoreBundle\Menu\AbstractMenuProvider;
use Contao\CoreBundle\Menu\PickerMenuProviderInterface;
use Contao\NewsArchiveModel;
use Contao\NewsModel;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the news picker.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class NewsPickerProvider extends AbstractMenuProvider implements PickerMenuProviderInterface
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

        if ($user->hasAccess('news', 'modules')) {
            $this->addMenuItem($menu, $factory, 'news', 'newsPicker', 'news');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTable($table)
    {
        return 'tl_news' === $table;
    }

    /**
     * {@inheritdoc}
     */
    public function processSelection($value)
    {
        return sprintf('{{news_url::%s}}', $value);
    }

    /**
     * {@inheritdoc}
     */
    public function canHandle(Request $request)
    {
        return $request->query->has('value') && false !== strpos($request->query->get('value'), '{{news_url::');
    }

    /**
     * {@inheritdoc}
     */
    public function getPickerUrl(Request $request)
    {
        $params = $request->query->all();
        $params['do'] = 'news';
        $params['value'] = str_replace(['{{news_url::', '}}'], '', $params['value']);

        if (null !== ($newsArchiveId = $this->getNewsArchiveId($params['value']))) {
            $params['table'] = 'tl_news';
            $params['id'] = $newsArchiveId;
        }

        return $this->route('contao_backend', $params);
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

        if (!(($newsModel = $newsAdapter->findById($id)) instanceof NewsModel)) {
            return null;
        }

        if (!(($newsArchive = $newsModel->getRelated('pid')) instanceof NewsArchiveModel)) {
            return null;
        }

        return $newsArchive->id;
    }
}
