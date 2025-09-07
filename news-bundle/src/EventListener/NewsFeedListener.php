<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\EventListener;

use Contao\ContentModel;
use Contao\Controller;
use Contao\CoreBundle\Cache\EntityCacheTags;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Image\ImageFactoryInterface;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\Environment;
use Contao\File;
use Contao\FilesModel;
use Contao\NewsArchiveModel;
use Contao\NewsBundle\Event\FetchArticlesForFeedEvent;
use Contao\NewsBundle\Event\TransformArticleForFeedEvent;
use Contao\NewsModel;
use Contao\StringUtil;
use Contao\UserModel;
use FeedIo\Feed\Item;
use FeedIo\Feed\Item\Author;
use FeedIo\Feed\Item\AuthorInterface;
use FeedIo\Feed\Item\Media;
use FeedIo\Feed\ItemInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @internal
 */
class NewsFeedListener
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly ImageFactoryInterface $imageFactory,
        private readonly ContentUrlGenerator $urlGenerator,
        private readonly InsertTagParser $insertTags,
        private readonly string $projectDir,
        private readonly EntityCacheTags $cacheTags,
        private readonly string $charset,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    #[AsEventListener(FetchArticlesForFeedEvent::class)]
    public function onFetchArticlesForFeed(FetchArticlesForFeedEvent $event): void
    {
        $pageModel = $event->getPageModel();
        $archives = $this->sortOutProtected(StringUtil::deserialize($pageModel->newsArchives, true));

        $featured = match ($pageModel->feedFeatured) {
            'featured' => true,
            'unfeatured' => false,
            default => null,
        };

        $newsModel = $this->framework->getAdapter(NewsModel::class);
        $articles = $newsModel->findPublishedByPids($archives, $featured, $pageModel->maxFeedItems);

        if ($articles) {
            $event->setArticles($articles->getModels());
        }
    }

    #[AsEventListener(TransformArticleForFeedEvent::class)]
    public function onTransformArticleForFeed(TransformArticleForFeedEvent $event): void
    {
        $article = $event->getArticle();

        $item = new Item();
        $item->setTitle(html_entity_decode($article->headline, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, $this->charset));
        $item->setLastModified((new \DateTime())->setTimestamp($article->date));
        $item->setLink($this->urlGenerator->generate($article, [], UrlGeneratorInterface::ABSOLUTE_URL));
        $item->setContent($this->getContent($article, $item, $event));
        $item->setPublicId($item->getLink());

        if ($author = $this->getAuthor($article)) {
            $item->setAuthor($author);
        }

        $enclosures = $this->getEnclosures($article, $event);

        foreach ($enclosures as $enclosure) {
            $item->addMedia($enclosure);
        }

        $event->setItem($item);
    }

    private function getContent(NewsModel $article, ItemInterface $item, TransformArticleForFeedEvent $event): string
    {
        $pageModel = $event->getPageModel();

        $environment = $this->framework->getAdapter(Environment::class);
        $controller = $this->framework->getAdapter(Controller::class);
        $contentModel = $this->framework->getAdapter(ContentModel::class);

        $description = $article->teaser ?? '';

        // Prepare the description
        if ('source_text' === $pageModel->feedSource) {
            $elements = $contentModel->findPublishedByPidAndTable($article->id, 'tl_news');

            if (null !== $elements) {
                $description = '';

                // Overwrite the request (see #7756)
                $environment->set('request', $item->getLink());

                foreach ($elements as $element) {
                    $description .= $controller->getContentElement($element);

                    $this->cacheTags->tagWithModelInstance($element);
                }

                $environment->set('request', $event->getRequest()->getUri());
            }
        }

        $description = $this->insertTags->replaceInline($description);

        return $controller->convertRelativeUrls($description, $item->getLink());
    }

    private function getAuthor(NewsModel $article): AuthorInterface|null
    {
        if ($authorModel = $this->framework->getAdapter(UserModel::class)->findById($article->author)) {
            return (new Author())->setName($authorModel->name);
        }

        return null;
    }

    private function getEnclosures(NewsModel $article, TransformArticleForFeedEvent $event): array
    {
        $uuids = [];

        if ($article->addImage && $article->singleSRC) {
            $uuids[] = $article->singleSRC;
        }

        if ($article->addEnclosure) {
            $uuids = [...$uuids, ...StringUtil::deserialize($article->enclosure, true)];
        }

        if (!$uuids) {
            return [];
        }

        $filesAdapter = $this->framework->getAdapter(FilesModel::class);
        $fileModels = $filesAdapter->findMultipleByUuids($uuids);

        if (null === $fileModels) {
            return [];
        }

        $baseUrl = $event->getBaseUrl();
        $pageModel = $event->getPageModel();
        $size = StringUtil::deserialize($pageModel->imgSize, true);
        $enclosures = [];

        foreach ($fileModels as $fileModel) {
            $file = new File($fileModel->path);

            if (!$file->exists()) {
                continue;
            }

            $fileUrl = $baseUrl.'/'.$file->path;
            $fileSize = $file->filesize;

            if ($size && $file->isImage) {
                $image = $this->imageFactory->create(Path::join($this->projectDir, $file->path), $size);
                $fileUrl = $baseUrl.'/'.$image->getUrl($this->projectDir);
                $file = new File(Path::makeRelative($image->getPath(), $this->projectDir));
                $fileSize = $file->exists() ? $file->filesize : null;
            }

            $media = (new Media())->setUrl($fileUrl)->setType($file->mime);

            if ($fileSize) {
                $media->setLength($fileSize);
            }

            $enclosures[] = $media;
        }

        return $enclosures;
    }

    /**
     * @param list<int> $archiveIds
     *
     * @return list<int>
     */
    private function sortOutProtected(array $archiveIds): array
    {
        $newsArchiveModel = $this->framework->getAdapter(NewsArchiveModel::class);
        $allowedIds = [];

        foreach ($newsArchiveModel->findMultipleByIds($archiveIds) ?? [] as $archive) {
            if ($archive->protected && !$this->authorizationChecker->isGranted(ContaoCorePermissions::MEMBER_IN_GROUPS, $archive->groups)) {
                continue;
            }

            $allowedIds[] = (int) $archive->id;
        }

        return $allowedIds;
    }
}
