<?php declare(strict_types=1);

namespace Contao\NewsBundle\EventListener;

use Contao\CoreBundle\Event\FeedEvent;
use Contao\CoreBundle\EventListener\AbstractFeedListener;
use Contao\CoreBundle\Feed\Enclosure;
use Contao\CoreBundle\Feed\Feed;
use Contao\CoreBundle\Feed\FeedItem;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\NewsFeedModel;
use Contao\NewsModel;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;

class FeedListener extends AbstractFeedListener
{
    private Connection $connection;

    private ContaoFramework $framework;

    public function __construct(Connection $connection, ContaoFramework $framework)
    {
        $this->connection = $connection;
        $this->framework = $framework;
    }

    public function supports(string $name): bool
    {
        $result = $this->connection->executeQuery(
            "SELECT COUNT(id) FROM {$this->connection->quoteIdentifier('tl_news_feed')} WHERE alias = ?",
            [$name]
        );

        return $result->rowCount() > 0;
    }

    public function generate(FeedEvent $event): void
    {
        $feedModel = $this->framework->getAdapter(NewsFeedModel::class)->findByAlias($event->getAlias());

        if (!$feedModel) {
            return;
        }

        $archiveIds = StringUtil::deserialize($feedModel->archives);

        if (empty($archiveIds)) {
            return;
        }

        $feed = Feed::create($event->getAlias())
            ->setTitle($feedModel->title)
            ->setDescription($feedModel->description)
            ->setLanguage($feedModel->language)
            ->setLink('https://foobar')
            ->setLastUpdated(new \DateTime('@'.$feedModel->tstamp))
        ;

        // TODO: Implement only featured
        $onlyFeatured = null;

        $models = $this->framework->getAdapter(NewsModel::class)->findPublishedByPids($archiveIds, $onlyFeatured, $feedModel->maxItems);

        if (null === $models) {
            return;
        }

        foreach ($models as $model) {
            $item = $this->transformModel($model, $feedModel);

            if ($item->getLastUpdated() > $feed->getLastUpdated()) {
                $feed->setLastUpdated($item->getLastUpdated());
            }

            $feed->addItem($item);
        }

        $event->setFeed($feed);
        $event->setType($feedModel->format);
    }

    private function transformModel(NewsModel $model, NewsFeedModel $feedModel): FeedItem
    {
        $item = FeedItem::create()
            ->setTitle($model->headline)
            ->setLastUpdated(new \DateTime('@'.$model->tstamp))
            ->setLink('')
            ->setGuid('')
        ;

        // TODO: Add content depending on $feedModel->source

        if ($model->addImage) {
            $item->addEnclosure(Enclosure::create($model->singleSRC));
        }

        return $item;
    }
}
