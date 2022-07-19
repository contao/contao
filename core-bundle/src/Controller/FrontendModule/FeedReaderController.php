<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Controller\FrontendModule;

use Contao\Config;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\ServiceAnnotation\FrontendModule;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\Environment;
use Contao\ModuleModel;
use Contao\Pagination;
use Contao\StringUtil;
use FeedIo\Feed;
use FeedIo\Feed\Item;
use FeedIo\FeedInterface;
use FeedIo\FeedIo;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @FrontendModule(category="miscellaneous")
 */
class FeedReaderController extends AbstractFrontendModuleController
{
    public function __construct(private readonly FeedIo $feedIo, private readonly LoggerInterface $logger, private readonly CacheInterface $cache)
    {
    }

    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $this->initializeContaoFramework();

        $feeds = [];

        foreach (StringUtil::trimsplit('[\n\t ]', trim($model->feedUrls)) as $url) {
            try {
                $feed = $this->cache->get('feed_reader_'.$model->id.'_'.md5($url), function (ItemInterface $item) use ($url, $model) {
                    $readerResult = $this->feedIo->read($url, new Feed());

                    if ($model->feedCache > 0) {
                        $item->expiresAfter($model->feedCache);
                    }

                    return $readerResult->getFeed();
                });
            } catch (\Exception $exception) {
                $feed = null;
                $this->logger->error(sprintf('Could not read feed %s: %s', $url, $exception->getMessage()));
                continue;
            }

            if ($feed instanceof FeedInterface) {
                $feeds[] = $feed;
            }
        }

        $template->set('feeds', $feeds);

        // Take the configured amount of items from each feed and merge them into one list
        /** @var list<array{feed: Feed, item: Item}> $elements */
        $elements = array_merge(
            ...array_map(
                static fn (FeedInterface $feed): array => array_map(
                    static fn (Item $item) => [
                        'feed' => $feed,
                        'item' => $item,
                    ],
                    \array_slice([...$feed], $model->skipFirst, $model->numberOfItems ?: null)
                ),
                $feeds
            )
        );

        usort($elements, static fn (array $a, array $b): int => $a['item']->getLastModified() <=> $b['item']->getLastModified());

        if ($model->perPage > 0) {
            $param = 'page_r'.$model->id;
            $page = $request->query->getInt($param, 1);
            $config = $this->container->get('contao.framework')->getAdapter(Config::class);

            // Do not index or cache the page if the page number is outside the range
            if ($page < 1 || $page > max(ceil(\count($elements) / $model->perPage), 1)) {
                throw new PageNotFoundException('Page not found: '.Environment::get('uri'));
            }

            // Set limit and offset
            $offset = ($page - 1) * $model->perPage;
            $limit = $model->perPage + $offset;

            $pagination = new Pagination(\count($elements), $model->perPage, $config->get('maxPaginationLinks'), $param);

            $template->set('pagination', $pagination->generate());
            $template->set('elements', \array_slice($elements, $offset, $limit));
        } else {
            $template->set('pagination', null);
            $template->set('elements', $elements);
        }

        return $template->getResponse();
    }
}
