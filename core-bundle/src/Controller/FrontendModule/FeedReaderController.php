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

use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Exception\PageOutOfRangeException;
use Contao\CoreBundle\Pagination\PaginationConfig;
use Contao\CoreBundle\Pagination\PaginationFactoryInterface;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\ModuleModel;
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

#[AsFrontendModule(category: 'miscellaneous')]
class FeedReaderController extends AbstractFrontendModuleController
{
    public function __construct(
        private readonly FeedIo $feedIo,
        private readonly LoggerInterface $logger,
        private readonly CacheInterface $cache,
        private readonly PaginationFactoryInterface $paginationFactory,
    ) {
    }

    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $this->initializeContaoFramework();

        $feeds = [];

        foreach (StringUtil::trimsplit('[\n\t ]', trim($model->rss_feed)) as $url) {
            try {
                $feed = $this->cache->get(
                    'feed_reader_'.$model->id.'_'.md5($url),
                    function (ItemInterface $item) use ($url, $model) {
                        $readerResult = $this->feedIo->read($url, new Feed());

                        if ($model->rss_cache > 0) {
                            $item->expiresAfter($model->rss_cache);
                        }

                        return $readerResult->getFeed();
                    },
                );
            } catch (\Exception $exception) {
                $feed = null;
                $this->logger->error(\sprintf('Could not read feed %s: %s', $url, $exception->getMessage()));

                continue;
            }

            if ($feed instanceof FeedInterface) {
                $feeds[] = $feed;
            }
        }

        $template->set('feeds', $feeds);

        // Take the configured amount of items from each feed and merge them into one list
        $elements = array_merge(
            ...array_map(
                static fn (FeedInterface $feed): array => array_map(
                    static fn (Item $item) => [
                        'feed' => $feed,
                        'item' => $item,
                    ],
                    \array_slice([...$feed], $model->skipFirst, $model->numberOfItems ?: null),
                ),
                $feeds,
            ),
        );

        usort($elements, static fn (array $a, array $b): int => $b['item']->getLastModified() <=> $a['item']->getLastModified());

        if ($model->perPage > 0) {
            $param = 'page_r'.$model->id;

            try {
                $pagination = $this->paginationFactory->create(new PaginationConfig($param, \count($elements), $model->perPage));
            } catch (PageOutOfRangeException $e) {
                throw new PageNotFoundException(\sprintf('Page not found: %s', $request->getUri()), previous: $e);
            }

            $template->set('pagination', $pagination);
            $template->set('elements', $pagination->getItemsForPage($elements));
        } else {
            $template->set('pagination', null);
            $template->set('elements', $elements);
        }

        return $template->getResponse();
    }
}
