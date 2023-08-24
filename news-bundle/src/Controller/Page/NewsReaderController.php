<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\Controller\Page;

use Contao\ArticleModel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsPage;
use Contao\CoreBundle\Exception\InternalServerErrorException;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\Routing\ResponseContext\CoreResponseContextFactory;
use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContext;
use Contao\CoreBundle\String\HtmlDecoder;
use Contao\CoreBundle\Util\UrlUtil;
use Contao\NewsModel;
use Contao\PageModel;
use Contao\StringUtil;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsPage()]
class NewsReaderController extends AbstractContentCompositionController
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly InsertTagParser $insertTagParser,
        private readonly CoreResponseContextFactory $responseContextFactory,
        private readonly HtmlDecoder $htmlDecoder,
    ) {
    }

    public function __invoke(Request $request, PageModel $pageModel): Response
    {
        if (!$request->attributes->has('auto_item')) {
            throw new PageNotFoundException('Page not found: '.$request->getRequestUri());
        }

        $this->framework->initialize();

        $archives = $this->getArchiveIds($pageModel);
        $newsModel = NewsModel::findPublishedByParentAndIdOrAlias($request->attributes->get('auto_item'), $archives);

        if (!$newsModel) {
            throw new PageNotFoundException('Page not found: '.$request->getRequestUri());
        }

        // Redirect if the news item has a target URL (see #1498)
        switch ($newsModel->source) {
            case 'internal':
                if ($page = PageModel::findPublishedById($newsModel->jumpTo)) {
                    return new RedirectResponse($page->getAbsoluteUrl(), 301);
                }

                throw new InternalServerErrorException('Invalid "jumpTo" value or target page not public');

            case 'article':
                if (($article = ArticleModel::findByPk($newsModel->articleId)) && ($page = PageModel::findPublishedById($article->pid))) {
                    return new RedirectResponse($page->getAbsoluteUrl('/articles/'.($article->alias ?: $article->id)), 301);
                }

                throw new InternalServerErrorException('Invalid "articleId" value or target page not public');

            case 'external':
                if ($newsModel->url) {
                    $url = $this->insertTagParser->replaceInline($newsModel->url);
                    $url = UrlUtil::makeAbsolute($url, $request->getBaseUrl());

                    return new RedirectResponse($url, 301);
                }

                throw new InternalServerErrorException('Empty target URL');
        }

        $request->attributes->set('_content', $newsModel);

        $responseContext = $this->responseContextFactory->createContaoWebpageResponseContext($pageModel);

        $this->updateHtmlHeadBag($responseContext, $newsModel);

        return $this->renderPage($pageModel, $responseContext);
    }

    private function getArchiveIds(PageModel $pageModel): array
    {
        $archives = StringUtil::deserialize($pageModel->newsArchives);

        // TODO: ModuleNews::sortOutProtected

        if (empty($archives) || !\is_array($archives)) {
            throw new InternalServerErrorException('The news_reader page ID '.$pageModel->id.' has no archives specified.');
        }

        return $archives;
    }

    private function updateHtmlHeadBag(ResponseContext $responseContext, NewsModel $newsModel): void
    {
        $htmlHeadBag = $responseContext->get(HtmlHeadBag::class);

        if ($newsModel->pageTitle) {
            $htmlHeadBag->setTitle($newsModel->pageTitle); // Already stored decoded
        } elseif ($newsModel->headline) {
            $htmlHeadBag->setTitle($this->htmlDecoder->inputEncodedToPlainText($newsModel->headline));
        }

        if ($newsModel->description) {
            $htmlHeadBag->setMetaDescription($this->htmlDecoder->inputEncodedToPlainText($newsModel->description));
        } elseif ($newsModel->teaser) {
            $htmlHeadBag->setMetaDescription($this->htmlDecoder->htmlToPlainText($newsModel->teaser));
        }

        if ($newsModel->robots) {
            $htmlHeadBag->setMetaRobots($newsModel->robots);
        }
    }
}
