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
use Contao\CoreBundle\Controller\AbstractController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsPage;
use Contao\CoreBundle\Routing\Page\DynamicRouteInterface;
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\NewsBundle\Event\FetchArticlesForFeedEvent;
use Contao\NewsBundle\Event\TransformArticleForFeedEvent;
use Contao\PageModel;
use FeedIo\Feed;
use FeedIo\Specification;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsPage(contentComposition: false)]
class NewsFeedController extends AbstractController implements DynamicRouteInterface
{
    final public const TYPE = 'news_feed';

    public static array $contentTypes = [
        'atom' => 'application/atom+xml',
        'json' => 'application/feed+json',
        'rss' => 'application/rss+xml',
    ];

    private array $urlSuffixes = [
        'atom' => '.xml',
        'json' => '.json',
        'rss' => '.xml',
    ];

    public function __construct(
        private readonly ContaoContext $contaoContext,
        private readonly Specification $specification,
        private readonly string $charset,
    ) {
    }

    public function __invoke(Request $request, PageModel $pageModel): Response
    {
        $this->initializeContaoFramework();

        $staticUrl = $this->contaoContext->getStaticUrl();
        $baseUrl = $staticUrl ?: $request->getSchemeAndHttpHost();

        $feed = new Feed();
        $feed->setTitle(html_entity_decode($pageModel->title, ENT_QUOTES, $this->charset));
        $feed->setDescription(html_entity_decode($pageModel->feedDescription ?? '', ENT_QUOTES, $this->charset));
        $feed->setLanguage($pageModel->language);

        $event = new FetchArticlesForFeedEvent($feed, $request, $pageModel);

        $dispatcher = $this->container->get('event_dispatcher');
        $dispatcher->dispatch($event);

        if (null !== ($articles = $event->getArticles())) {
            foreach ($articles as $article) {
                $event = new TransformArticleForFeedEvent($article, $feed, $pageModel, $request, $baseUrl);
                $dispatcher->dispatch($event);

                $feed->add($event->getItem());

                $this->tagResponse($article);
                $this->tagResponse('contao.db.tl_news_archive.'.$article->pid);
            }
        }

        $formatter = $this->specification->getStandard($pageModel->feedFormat)->getFormatter();

        $response = new Response($formatter->toString($feed));
        $response->headers->set('Content-Type', self::$contentTypes[$pageModel->feedFormat]);

        $this->setCacheHeaders($response, $pageModel);

        return $response;
    }

    public function configurePageRoute(PageRoute $route): void
    {
        $format = $route->getPageModel()->feedFormat;

        if (!isset($this->urlSuffixes[$format])) {
            throw new \RuntimeException(sprintf('%s is not a valid format. Must be one of: %s', $format, implode(',', array_keys($this->urlSuffixes))));
        }

        $route->setUrlSuffix($this->urlSuffixes[$format]);
    }

    public function getUrlSuffixes(): array
    {
        return array_unique(array_values($this->urlSuffixes));
    }
}
