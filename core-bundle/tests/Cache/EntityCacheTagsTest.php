<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Cache;

use Contao\ArticleModel;
use Contao\CoreBundle\Cache\EntityCacheTags;
use Contao\CoreBundle\Tests\Doctrine\DoctrineTestCase;
use Contao\CoreBundle\Tests\Fixtures\Entity\Author;
use Contao\CoreBundle\Tests\Fixtures\Entity\BlogPost;
use Contao\CoreBundle\Tests\Fixtures\Entity\Comment;
use Contao\CoreBundle\Tests\Fixtures\Entity\Tag;
use Contao\Model\Collection;
use Contao\PageModel;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\DocParser;
use Doctrine\Common\Collections\ArrayCollection;
use FOS\HttpCache\CacheInvalidator;
use FOS\HttpCache\ResponseTagger;

class EntityCacheTagsTest extends DoctrineTestCase
{
    protected function tearDown(): void
    {
        $this->resetStaticProperties([[AnnotationRegistry::class, ['failedToAutoload']], DocParser::class]);

        parent::tearDown();
    }

    public function testGetTagForEntityClass(): void
    {
        $entityCacheTags = $this->getEntityCacheTags();

        $this->assertSame('contao.db.tl_blog_post', $entityCacheTags->getTagForEntityClass(BlogPost::class));
    }

    public function testThrowsIfClassIsNoEntity(): void
    {
        $entityCacheTags = $this->getEntityCacheTags();

        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('The given class name "stdClass" is no valid entity class.');

        $entityCacheTags->getTagForEntityClass(\stdClass::class);
    }

    public function testGetTagForEntityInstance(): void
    {
        $entityCacheTags = $this->getEntityCacheTags();
        $post = (new BlogPost())->setId(5);

        $this->assertSame('contao.db.tl_blog_post.5', $entityCacheTags->getTagForEntityInstance($post));
    }

    public function testThrowsIfInstanceIsNoEntity(): void
    {
        $entityCacheTags = $this->getEntityCacheTags();

        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('The given object of type "stdClass" is no valid entity instance.');

        $entityCacheTags->getTagForEntityInstance(new \stdClass());
    }

    public function testGetTagForModelClass(): void
    {
        $entityCacheTags = $this->getEntityCacheTags();

        $this->assertSame('contao.db.tl_page', $entityCacheTags->getTagForModelClass(PageModel::class));
    }

    public function testThrowsIfClassIsNoModel(): void
    {
        $entityCacheTags = $this->getEntityCacheTags();

        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('The given class name "stdClass" is no valid model class.');

        /** @phpstan-ignore-next-line */
        $entityCacheTags->getTagForModelClass(\stdClass::class);
    }

    public function testGetTagForModelInstance(): void
    {
        $entityCacheTags = $this->getEntityCacheTags();

        /** @var PageModel $page */
        $page = (new \ReflectionClass(PageModel::class))->newInstanceWithoutConstructor();
        $page->id = 5;

        $this->assertSame('contao.db.tl_page.5', $entityCacheTags->getTagForModelInstance($page));
    }

    /**
     * @param mixed $argument
     *
     * @dataProvider getArguments
     */
    public function testGetTags($argument, array $expectedTags): void
    {
        $entityCacheTags = $this->getEntityCacheTags();

        $this->assertSame($expectedTags, $entityCacheTags->getTagsFor($argument));
    }

    public function getArguments(): \Generator
    {
        yield 'single tag' => [
            'foo',
            ['foo'],
        ];

        yield 'list of tags' => [
            ['foo', 'bar'],
            ['foo', 'bar'],
        ];

        yield 'general tag for entity' => [
            BlogPost::class,
            ['contao.db.tl_blog_post'],
        ];

        yield 'general tag for model' => [
            PageModel::class,
            ['contao.db.tl_page'],
        ];

        $comment1 = (new Comment())->setId(11);
        $comment2 = (new Comment())->setId(12);
        $author = (new Author())->setId(100);
        $tag = (new Tag())->setId(42);

        $post = (new BlogPost())
            ->setId(5)
            ->setAuthor($author)
            ->setComments(new ArrayCollection([$comment1, $comment2]))
            ->setTags(new ArrayCollection([$tag]))
        ;

        /** @var PageModel $page1 */
        $page1 = (new \ReflectionClass(PageModel::class))->newInstanceWithoutConstructor();
        $page1->id = 5;

        /** @var PageModel $page2 */
        $page2 = (new \ReflectionClass(PageModel::class))->newInstanceWithoutConstructor();
        $page2->id = 6;

        $modelCollection = new Collection([$page1, $page2], 'tl_page');

        yield 'specific tag for entity instance' => [
            $post,
            ['contao.db.tl_blog_post.5'],
        ];

        yield 'specific tag for model instance' => [
            $page1,
            ['contao.db.tl_page.5'],
        ];

        yield 'mixed' => [
            [$post, $post->getAuthor(), $post->getComments(), $post->getTags(), $modelCollection, ArticleModel::class, 'foo'],
            [
                'contao.db.tl_blog_post.5',
                'contao.db.tl_author.100',
                'contao.db.tl_comment.11',
                'contao.db.tl_comment.12',
                'contao.db.tl_tag.42',
                'contao.db.tl_page.5',
                'contao.db.tl_page.6',
                'contao.db.tl_article',
                'foo',
            ],
        ];

        yield 'class-string, but not an entity or model' => [
            [\stdClass::class],
            ['stdClass'],
        ];

        yield 'empty and null' => [
            ['', null, [], 'foo'],
            ['foo'],
        ];
    }

    public function testDelegatesToResponseTagger(): void
    {
        $responseTagger = $this->createMock(ResponseTagger::class);
        $responseTagger
            ->expects($this->exactly(5))
            ->method('addTags')
            ->withConsecutive(
                [['contao.db.tl_blog_post']],
                [['contao.db.tl_blog_post.1']],
                [['contao.db.tl_page']],
                [['contao.db.tl_page.2']],
                [['contao.db.tl_blog_post.1', 'contao.db.tl_page.2', 'foo']],
            )
        ;

        $post = (new BlogPost())->setId(1);

        /** @var PageModel $page */
        $page = (new \ReflectionClass(PageModel::class))->newInstanceWithoutConstructor();
        $page->id = 2;

        $entityCacheTags = $this->getEntityCacheTags($responseTagger);
        $entityCacheTags->tagWithEntityClass(BlogPost::class);
        $entityCacheTags->tagWithEntityInstance($post);
        $entityCacheTags->tagWithModelClass(PageModel::class);
        $entityCacheTags->tagWithModelInstance($page);
        $entityCacheTags->tagWith([$post, $page, 'foo']);
    }

    public function testDelegatesToCacheInvalidator(): void
    {
        $cacheInvalidator = $this->createMock(CacheInvalidator::class);
        $cacheInvalidator
            ->expects($this->exactly(5))
            ->method('invalidateTags')
            ->withConsecutive(
                [['contao.db.tl_blog_post']],
                [['contao.db.tl_blog_post.1']],
                [['contao.db.tl_page']],
                [['contao.db.tl_page.2']],
                [['contao.db.tl_blog_post.1', 'contao.db.tl_page.2', 'foo']],
            )
        ;

        $post = (new BlogPost())->setId(1);

        /** @var PageModel $page */
        $page = (new \ReflectionClass(PageModel::class))->newInstanceWithoutConstructor();
        $page->id = 2;

        $entityCacheTags = $this->getEntityCacheTags(null, $cacheInvalidator);
        $entityCacheTags->invalidateTagsForEntityClass(BlogPost::class);
        $entityCacheTags->invalidateTagsForEntityInstance($post);
        $entityCacheTags->invalidateTagsForModelClass(PageModel::class);
        $entityCacheTags->invalidateTagsForModelInstance($page);
        $entityCacheTags->invalidateTagsFor([$post, $page, 'foo']);
    }

    private function getEntityCacheTags(ResponseTagger $responseTagger = null, CacheInvalidator $cacheInvalidator = null): EntityCacheTags
    {
        return new EntityCacheTags(
            $this->getTestEntityManager(),
            $responseTagger ?? $this->createMock(ResponseTagger::class),
            $cacheInvalidator ?? $this->createMock(CacheInvalidator::class)
        );
    }
}
