<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\PageType;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\PageType\AbstractPageType;
use Contao\CoreBundle\PageType\AbstractSinglePageType;
use Contao\CoreBundle\PageType\HasLegacyPageInterface;
use Contao\NewsArchiveModel;
use Contao\NewsModel;
use Contao\PageModel;
use Contao\PageRegular;
use Symfony\Component\Routing\Route;
use function preg_replace_callback;

class NewsReaderPageType extends AbstractPageType implements HasLegacyPageInterface
{
    protected $parameters = [
        'archive_alias' => '.+',
        'archive_id'    => '[[:digit:]]+',
        'news_alias'    => '.+',
        'news_id'       => '[[:digit:]]+',
        'news_year'     => '[[:digit:]]{4}',
        'news_month'    => '[[:digit:]]{2}',
        'news_day'      => '[[:digit:]]{2}',
    ];

    public function getLegacyPageClass() : string
    {
        return PageRegular::class;
    }

    public function getRequiredAliasParameters() : array
    {
        return ['news_alias'];
    }

    public function getRoutes(PageModel $pageModel, bool $prependLocale, string $urlSuffix) : iterable
    {
        $newsArchiveIds = $this->findNewsArchiveIds($pageModel);

        foreach ($newsArchiveIds as $newsArchiveId) {
            $newsModels = NewsModel::findPublishedDefaultByPid($newsArchiveId);
            if (null === $newsModels) {
                continue;
            }

            foreach ($newsModels as $newsModel) {
                yield sprintf('tl_news.%s', $newsModel->id) => new Route(
                    $this->getNewsRoutePath($pageModel, $newsModel, $prependLocale, $urlSuffix),
                    $this->getNewsRouteDefaults($pageModel, $newsModel),
                    ['parameters' => '(/.+)?'],
                    [],
                    $pageModel->domain,
                    $pageModel->rootUseSSL ? 'https' : null,
                    []
                );
            }
        }
    }

    protected function getRoutePath(PageModel $pageModel, bool $prependLocale, string $urlSuffix): string
    {
        $path = sprintf('/%s{parameters}%s', $pageModel->alias ?: $pageModel->id, $urlSuffix);

        if ($prependLocale) {
            $path = '/{_locale}'.$path;
        }

        return $path;
    }

    protected function getNewsRoutePath(PageModel $pageModel, NewsModel $newsModel, bool $prependLocale, string $urlSuffix): string
    {
        return preg_replace_callback(
            '#{([^}]+)}#',
            static function (array $matches) use ($newsModel) {
                switch ($matches[1]) {
                    case 'news_alias':
                        return $newsModel->alias;

                    case 'news_id':
                        return $newsModel->id;

                    case 'news_year':
                        return date('Y', (int) $newsModel->date);

                    case 'news_month':
                        return date('m', (int) $newsModel->date);

                    case 'news_day':
                        return date('d', (int) $newsModel->date);

                    default:
                        return $matches[0];
                }
            },
            $this->getRoutePath($pageModel, $prependLocale, $urlSuffix)
        );
    }

    protected function getNewsRouteDefaults(PageModel $pageModel, NewsModel $newsModel): array
    {
        return [
            '_token_check' => true,
            '_controller' => 'Contao\FrontendIndex::renderPage',
            '_scope' => ContaoCoreBundle::SCOPE_FRONTEND,
            '_locale' => $pageModel->rootLanguage,
            'pageModel' => $pageModel,
            'pageTypeConfig' => new NewsReaderPageTypeConfig($this, $pageModel, $newsModel)
        ];
    }

    protected function findNewsArchiveIds($pageModel): array
    {
        $collection = NewsArchiveModel::findByJumpTo($pageModel->id);
        if (null === $collection) {
            return [];
        }

        return $collection->fetchEach('id');
    }
}
