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
use Contao\Model\Collection as ModelCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\MappingException;
use FOS\HttpCache\CacheInvalidator;
use FOS\HttpCache\ResponseTagger;

/**
 * Use this helper service to derive "contao.db.*" cache tags from entity/model
 * classes and instances. The tagWith*() and invalidateFor*() shortcut methods
 * directly tag the response or invalidate tags. If your application does not
 * use response tagging, these methods are no-ops.
 */
class EntityCacheTags
{
    /**
     * @var array<string, ClassMetadata<object>>
     */
    private array $classMetadata = [];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ResponseTagger|null $responseTagger = null,
        private readonly CacheInvalidator|null $cacheInvalidator = null,
    ) {
    }

    /**
     * Derives a cache tag from an entity class and returns it.
     *
     * @param class-string $className
     */
    public function getTagForEntityClass(string $className): string
    {
        if (!$metadata = $this->getClassMetadata($className)) {
            throw new \InvalidArgumentException(sprintf('The given class name "%s" is no valid entity class.', $className));
        }

        return sprintf('contao.db.%s', $metadata->getTableName());
    }

    /**
     * Derives a cache tag from an entity instance and returns it.
     */
    public function getTagForEntityInstance(object $instance): string
    {
        if (!$metadata = $this->getClassMetadata($instance::class)) {
            throw new \InvalidArgumentException(sprintf('The given object of type "%s" is no valid entity instance.', $instance::class));
        }

        $identifier = $this->entityManager
            ->getUnitOfWork()
            ->getSingleIdentifierValue($instance)
        ;

        return sprintf('contao.db.%s.%s', $metadata->getTableName(), $identifier);
    }

    /**
     * Derives a cache tag from a model class and returns it.
     *
     * @param class-string<Model> $className
     */
    public function getTagForModelClass(string $className): string
    {
        if (!$this->isModel($className)) {
            throw new \InvalidArgumentException(sprintf('The given class name "%s" is no valid model class.', $className));
        }

        return sprintf('contao.db.%s', \call_user_func([$className, 'getTable']));
    }

    /**
     * Derives a cache tag from a model instance and returns it.
     */
    public function getTagForModelInstance(Model $instance): string
    {
        return sprintf('contao.db.%s.%s', $instance::getTable(), $instance->id);
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
        if (!$target) {
            return [];
        }

        if (\is_string($target)) {
            if ($this->isValidFQCN($target)) {
                if ($this->isModel($target)) {
                    return [$this->getTagForModelClass($target)];
                }

                try {
                    return [$this->getTagForEntityClass($target)];
                } catch (\InvalidArgumentException) {
                    // ignore
                }
            }

            return [$target];
        }

        if (\is_array($target) || $target instanceof Collection || $target instanceof ModelCollection) {
            $tags = [];

            foreach ($target as $part) {
                $tags = [...$tags, ...$this->getTagsFor($part)];
            }

            return array_unique($tags);
        }

        if ($this->isModel($target)) {
            /** @var Model $target */
            return [$this->getTagForModelInstance($target)];
        }

        return [$this->getTagForEntityInstance($target)];
    }

    /**
     * Derives a cache tag from an entity class and adds it to the response.
     */
    public function tagWithEntityClass(string $className): void
    {
        if (!$this->responseTagger) {
            return;
        }

        $this->responseTagger->addTags([$this->getTagForEntityClass($className)]);
    }

    /**
     * Derives a cache tag from an entity instance and adds it to the response.
     */
    public function tagWithEntityInstance(object $instance): void
    {
        if (!$this->responseTagger) {
            return;
        }

        $this->responseTagger->addTags([$this->getTagForEntityInstance($instance)]);
    }

    /**
     * Derives a cache tag from a model class and adds it to the response.
     */
    public function tagWithModelClass(string $className): void
    {
        if (!$this->responseTagger) {
            return;
        }

        $this->responseTagger->addTags([$this->getTagForModelClass($className)]);
    }

    /**
     * Derives a cache tag from a model instance and adds it to the response.
     */
    public function tagWithModelInstance(Model $instance): void
    {
        if (!$this->responseTagger) {
            return;
        }

        $this->responseTagger->addTags([$this->getTagForModelInstance($instance)]);
    }

    /**
     * Derives cache tags and adds them to the response.
     *
     * See getTagsFor() method for the allowed parameters.
     */
    public function tagWith(array|object|string|null $target): void
    {
        if (!$this->responseTagger) {
            return;
        }

        $this->responseTagger->addTags($this->getTagsFor($target));
    }

    /**
     * Derives a cache tag from an entity class and invalidates it.
     */
    public function invalidateTagsForEntityClass(string $className): void
    {
        if (!$this->cacheInvalidator) {
            return;
        }

        $this->cacheInvalidator->invalidateTags([$this->getTagForEntityClass($className)]);
    }

    /**
     * Derives a cache tag from an entity class and invalidates it.
     */
    public function invalidateTagsForEntityInstance(object $instance): void
    {
        if (!$this->cacheInvalidator) {
            return;
        }

        $this->cacheInvalidator->invalidateTags([$this->getTagForEntityInstance($instance)]);
    }

    /**
     * Derives a cache tag from an entity class and invalidates it.
     */
    public function invalidateTagsForModelClass(string $className): void
    {
        if (!$this->cacheInvalidator) {
            return;
        }

        $this->cacheInvalidator->invalidateTags([$this->getTagForModelClass($className)]);
    }

    /**
     * Derives a cache tag from an entity class and invalidates it.
     */
    public function invalidateTagsForModelInstance(Model $instance): void
    {
        if (!$this->cacheInvalidator) {
            return;
        }

        $this->cacheInvalidator->invalidateTags([$this->getTagForModelInstance($instance)]);
    }

    /**
     * Derives cache tags and invalidates them.
     *
     * See getTagsFor() method for the allowed parameters.
     */
    public function invalidateTagsFor(array|object|string|null $target): void
    {
        if (!$this->cacheInvalidator) {
            return;
        }

        $this->cacheInvalidator->invalidateTags($this->getTagsFor($target));
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $className
     *
     * @return ?ClassMetadata<T>
     */
    private function getClassMetadata(string $className): ClassMetadata|null
    {
        $getMetadata = function (string $className) {
            try {
                return $this->entityManager->getClassMetadata($className);
            } catch (MappingException) {
                return null;
            }
        };

        return $this->classMetadata[$className] ??= $getMetadata($className);
    }

    private function isValidFQCN(string $target): bool
    {
        return 1 === preg_match('/^(?:[a-z_\x80-\xff][a-z0-9_\x80-\xff]*\\\\?)+(?<!\\\\)$/i', $target);
    }

    private function isModel(object|string $classStringOrObject): bool
    {
        return is_subclass_of($classStringOrObject, Model::class);
    }
}
