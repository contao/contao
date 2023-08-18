<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Controller\ContentElement;

use Contao\ArticleModel;
use Contao\ContentModel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement(category: 'includes')]
class TeaserController extends AbstractContentElementController
{
    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        if (null === ($articleAndPage = $this->getArticleAndPage($model))) {
            $response = new Response();

            $this->markResponseForInternalCaching($response);

            return $response;
        }

        [$article, $page] = $articleAndPage;

        $href = $page->getFrontendUrl(
            sprintf(
                '/articles/%s%s',
                'main' !== $article->inColumn ? "$article->inColumn:" : '',
                $article->alias ?: $article->id
            )
        );

        $template->set('article', $article);
        $template->set('page', $page);
        $template->set('href', $href);

        return $template->getResponse();
    }

    /**
     * @return array{0: ArticleModel, 1: PageModel}|null
     */
    private function getArticleAndPage(ContentModel $model): array|null
    {
        $this->initializeContaoFramework();

        $articleModel = $this->getContaoAdapter(ArticleModel::class);
        $article = $articleModel->findPublishedById($model->article);

        if (!$article instanceof ArticleModel) {
            return null;
        }

        $pageModel = $this->getContaoAdapter(PageModel::class);
        $page = $pageModel->findPublishedById($article->pid);

        return $page instanceof PageModel ? [$article, $page] : null;
    }
}
