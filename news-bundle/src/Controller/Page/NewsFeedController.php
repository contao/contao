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

use Contao\CoreBundle\Asset\ContaoContext;
use Contao\CoreBundle\Controller\Page\AbstractFeedPageController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsPage;
use Contao\NewsBundle\Event\FetchArticlesForFeedEvent;
use Contao\NewsBundle\Event\TransformArticleForFeedEvent;
use Contao\PageModel;
use Contao\StringUtil;
use FeedIo\Feed;
use FeedIo\Specification;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsPage(path: '', contentComposition: false)]
class NewsFeedController extends AbstractFeedPageController
{
    final public const TYPE = 'news_feed';

    public function __construct(
        private readonly ContaoContext $contaoContext,
        private readonly Specification $specification,
        private readonly string $charset,
        private readonly bool $isDebug = false,
    ) {
    }

    public function __invoke(Request $request, PageModel $pageModel): Response
    {
        $this->initializeContaoFramework();

        $staticUrl = $this->contaoContext->getBasePath();
        $baseUrl = $staticUrl ?: $request->getSchemeAndHttpHost();

        $feed = new Feed();
        $feed->setTitle(html_entity_decode($pageModel->title, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, $this->charset));
        $feed->setDescription(html_entity_decode($pageModel->feedDescription ?? '', ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, $this->charset));
        $feed->setLanguage($pageModel->language);

        $event = new FetchArticlesForFeedEvent($feed, $request, $pageModel);

        $dispatcher = $this->container->get('event_dispatcher');
        $dispatcher->dispatch($event);

        foreach ($event->getArticles() ?? [] as $article) {
            $event = new TransformArticleForFeedEvent($article, $feed, $pageModel, $request, $baseUrl);
            $dispatcher->dispatch($event);

            if (!$item = $event->getItem()) {
                continue;
            }

            $feed->add($item);
            $this->tagResponse($article);
        }

        $contentType = self::$contentTypes[$pageModel->feedFormat];

        // Use a more generic Content-Type for the response header in debug mode (see #8589)
        if ($this->isDebug) {
            $contentType = preg_replace('~/[a-z]+\+~', '/', $contentType);
        }

        $formatter = $this->specification->getStandard($pageModel->feedFormat)->getFormatter();

        $response = new Response($formatter->toString($feed));
        $response->headers->set('Content-Type', $contentType);

        $this->setCacheHeaders($response, $pageModel);

        // Always add the response tags for the selected archives
        $archiveIds = StringUtil::deserialize($pageModel->newsArchives, true);
        $this->tagResponse(array_map(static fn ($id): string => 'contao.db.tl_news_archive.'.$id, $archiveIds));

        return $response;
    }
}
