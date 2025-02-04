<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Cache;

use Contao\Model;

/**
 * Use this helper service to derive "contao.db.*" cache tags from entity/model
 * classes and instances. The tagWith*() and invalidateFor*() shortcut methods
 * directly tag the response or invalidate tags. If your application does not use
 * response tagging, these methods are no-ops.
 *
 * @deprecated Deprecated since Contao 5.5, to be removed in Contao 6;
 *             use the CacheTagManager instead.
 */
class EntityCacheTags
{
    public function __construct(private readonly CacheTagManager $cacheTagManager)
    {
        trigger_deprecation('contao/core-bundle', '5.5', 'Using the EntityCacheTags service has been deprecated and will no longer work in Contao 6. Use the CacheTagManager instead.');
    }

    /**
     * Derives a cache tag from an entity class and returns it.
     *
     * @param class-string $className
     */
    public function getTagForEntityClass(string $className): string
    {
        return $this->cacheTagManager->getTagForEntityClass($className);
    }

    /**
     * Derives a cache tag from an entity instance and returns it.
     */
    public function getTagForEntityInstance(object $instance): string
    {
        return $this->cacheTagManager->getTagForEntityInstance($instance);
    }

    /**
     * Derives a cache tag from a model class and returns it.
     *
     * @param class-string<Model> $className
     */
    public function getTagForModelClass(string $className): string
    {
        return $this->cacheTagManager->getTagForModelClass($className);
    }

    /**
     * Derives a cache tag from a model instance and returns it.
     */
    public function getTagForModelInstance(Model $instance): string
    {
        return $this->cacheTagManager->getTagForModelInstance($instance);
    }

    /**
     * Derives cache tags and returns them.
     *
     * The $target parameter can be an array of or a single …
     *   - entity class-string
     *   - entity instance
     *   - entity collection (@see Collection)
     *   - model class-string
     *   - model instance
     *   - model collection (@see ModelCollection)
     *   - cache tag as a string
     *
     * You can safely pass empty collections or null.
     *
     * Demo usage:
     *
     *   getTagsFor(BlogPost::class); // ['contao.db.tl_blog_post']
     *   getTagsFor([$blogPost, $blogPost->getComments()]); // ['contao.db.tl_blog_post.5', 'contao.db.tl_comment.11', 'contao.db.tl_comment.12']
     *   getTagsFor(PageModel::class); // ['contao.db.tl_page']
     *   getTagsFor([$objPage, null, 'foo']); // ['contao.db.tl_page.42', 'foo']
     *
     * @return array<int, string>
     */
    public function getTagsFor(array|object|string|null $target): array
    {
        return $this->cacheTagManager->getTagsFor($target);
    }

    /**
     * Derives a cache tag from an entity class and adds it to the response.
     */
    public function tagWithEntityClass(string $className): void
    {
        $this->cacheTagManager->tagWithEntityClass($className);
    }

    /**
     * Derives a cache tag from an entity instance and adds it to the response.
     */
    public function tagWithEntityInstance(object $instance): void
    {
        $this->cacheTagManager->tagWithEntityInstance($instance);
    }

    /**
     * Derives a cache tag from a model class and adds it to the response.
     */
    public function tagWithModelClass(string $className): void
    {
        $this->cacheTagManager->tagWithModelClass($className);
    }

    /**
     * Derives a cache tag from a model instance and adds it to the response.
     */
    public function tagWithModelInstance(Model $instance): void
    {
        $this->cacheTagManager->tagWithModelInstance($instance);
    }

    /**
     * Derives cache tags and adds them to the response.
     *
     * See getTagsFor() method for the allowed parameters.
     */
    public function tagWith(array|object|string|null $target): void
    {
        $this->cacheTagManager->tagWith($target);
    }

    /**
     * Derives a cache tag from an entity class and invalidates it.
     */
    public function invalidateTagsForEntityClass(string $className): void
    {
        $this->cacheTagManager->invalidateTagsForEntityClass($className);
    }

    /**
     * Derives a cache tag from an entity class and invalidates it.
     */
    public function invalidateTagsForEntityInstance(object $instance): void
    {
        $this->cacheTagManager->invalidateTagsForEntityInstance($instance);
    }

    /**
     * Derives a cache tag from an entity class and invalidates it.
     */
    public function invalidateTagsForModelClass(string $className): void
    {
        $this->cacheTagManager->invalidateTagsForModelClass($className);
    }

    /**
     * Derives a cache tag from an entity class and invalidates it.
     */
    public function invalidateTagsForModelInstance(Model $instance): void
    {
        $this->cacheTagManager->invalidateTagsForModelInstance($instance);
    }

    /**
     * Derives cache tags and invalidates them.
     *
     * See getTagsFor() method for the allowed parameters.
     */
    public function invalidateTagsFor(array|object|string|null $target): void
    {
        $this->cacheTagManager->invalidateTagsFor($target);
    }
}
