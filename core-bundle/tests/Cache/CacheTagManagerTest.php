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
use Contao\Config;
use Contao\CoreBundle\Cache\CacheTagManager;
use Contao\CoreBundle\Event\InvalidateCacheTagsEvent;
use Contao\CoreBundle\Tests\Doctrine\DoctrineTestCase;
use Contao\CoreBundle\Tests\Fixtures\Entity\Author;
use Contao\CoreBundle\Tests\Fixtures\Entity\BlogPost;
use Contao\CoreBundle\Tests\Fixtures\Entity\Comment;
use Contao\CoreBundle\Tests\Fixtures\Entity\Tag;
use Contao\DcaExtractor;
use Contao\DcaLoader;
use Contao\Model\Collection;
use Contao\PageModel;
use Contao\System;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\DocParser;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use FOS\HttpCache\CacheInvalidator;
use FOS\HttpCache\ResponseTagger;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CacheTagManagerTest extends DoctrineTestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_MIME'], $GLOBALS['TL_TEST'], $GLOBALS['TL_LANG']);

        $this->resetStaticProperties([
            [AnnotationRegistry::class, ['failedToAutoload']],
            Config::class,
            DcaExtractor::class,
            DcaLoader::class,
            DocParser::class,
            System::class,
        ]);

        parent::tearDown();
    }

    public function testDispatchesEvent(): void
    {
        $tags = ['foo', 'bar'];

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(
                function (InvalidateCacheTagsEvent $event) use ($tags) {
                    $this->assertSame($tags, $event->getTags());

                    return true;
                },
            ))
        ;

        $cacheTagManager = new CacheTagManager(
            $this->createStub(EntityManagerInterface::class),
            $eventDispatcher,
        );

        $cacheTagManager->invalidateTags($tags);
    }

    public function testGetTagForEntityClass(): void
    {
        $cacheTagManager = $this->getCacheTagManager($this->createStub(CacheInvalidator::class));

        $this->assertSame('contao.db.tl_blog_post', $cacheTagManager->getTagForEntityClass(BlogPost::class));
    }

    public function testThrowsIfClassIsNoEntity(): void
    {
        $cacheTagManager = $this->getCacheTagManager($this->createStub(CacheInvalidator::class));

        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('The given class name "stdClass" is no valid entity class.');

        $cacheTagManager->getTagForEntityClass(\stdClass::class);
    }

    public function testGetTagForEntityInstance(): void
    {
        $cacheTagManager = $this->getCacheTagManager($this->createStub(CacheInvalidator::class));
        $post = (new BlogPost())->setId(5);

        $this->assertSame('contao.db.tl_blog_post.5', $cacheTagManager->getTagForEntityInstance($post));
    }

    public function testThrowsIfInstanceIsNoEntity(): void
    {
        $cacheTagManager = $this->getCacheTagManager($this->createStub(CacheInvalidator::class));

        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('The given object of type "stdClass" is no valid entity instance.');

        $cacheTagManager->getTagForEntityInstance(new \stdClass());
    }

    public function testGetTagForModelClass(): void
    {
        $cacheTagManager = $this->getCacheTagManager($this->createStub(CacheInvalidator::class));

        $this->assertSame('contao.db.tl_page', $cacheTagManager->getTagForModelClass(PageModel::class));
    }

    public function testThrowsIfClassIsNoModel(): void
    {
        $cacheTagManager = $this->getCacheTagManager($this->createStub(CacheInvalidator::class));

        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('The given class name "stdClass" is no valid model class.');

        /** @phpstan-ignore argument.type */
        $cacheTagManager->getTagForModelClass(\stdClass::class);
    }

    public function testGetTagForModelInstance(): void
    {
        System::setContainer($this->getContainerWithFixtures());

        $cacheTagManager = $this->getCacheTagManager($this->createStub(CacheInvalidator::class));

        $page = new PageModel();
        $page->id = 5;

        $this->assertSame('contao.db.tl_page.5', $cacheTagManager->getTagForModelInstance($page));
    }

    #[DataProvider('getArguments')]
    public function testGetTags(mixed $argument, array $expectedTags): void
    {
        $cacheTagManager = $this->getCacheTagManager($this->createStub(CacheInvalidator::class));

        $this->assertSame($expectedTags, $cacheTagManager->getTagsFor($argument));
    }

    public static function getArguments(): iterable
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

        yield 'specific tag for entity instance' => [
            $post,
            ['contao.db.tl_blog_post.5'],
        ];

        yield 'mixed' => [
            [$post, $post->getAuthor(), $post->getComments(), $post->getTags(), ArticleModel::class, 'foo'],
            [
                'contao.db.tl_blog_post.5',
                'contao.db.tl_author.100',
                'contao.db.tl_comment.11',
                'contao.db.tl_comment.12',
                'contao.db.tl_tag.42',
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

    public function testGetPageTags(): void
    {
        System::setContainer($this->getContainerWithFixtures());

        $page1 = new PageModel();
        $page1->id = 5;

        $page2 = new PageModel();
        $page2->id = 6;

        $modelCollection = new Collection([$page1, $page2], 'tl_page');
        $cacheTagManager = $this->getCacheTagManager($this->createStub(CacheInvalidator::class));

        $this->assertSame(['contao.db.tl_page.5'], $cacheTagManager->getTagsFor($page1));
        $this->assertSame(['contao.db.tl_page.5', 'contao.db.tl_page.6'], $cacheTagManager->getTagsFor($modelCollection));
    }

    public function testDelegatesToResponseTagger(): void
    {
        System::setContainer($this->getContainerWithFixtures());

        $responseTagger = $this->createMock(ResponseTagger::class);
        $matcher = $this->exactly(5);

        $expected = [
            [['contao.db.tl_blog_post']],
            [['contao.db.tl_blog_post.1']],
            [['contao.db.tl_page']],
            [['contao.db.tl_page.2']],
            [['contao.db.tl_blog_post.1', 'contao.db.tl_page.2', 'foo']],
        ];

        $responseTagger
            ->expects($matcher)
            ->method('addTags')
            ->with($this->callback(
                static fn (...$args) => $args === $expected[$matcher->numberOfInvocations() - 1],
            ))
        ;

        $post = (new BlogPost())->setId(1);

        $page = new PageModel();
        $page->id = 2;

        $cacheTagManager = $this->getCacheTagManager($this->createStub(CacheInvalidator::class), $responseTagger);
        $cacheTagManager->tagWithEntityClass(BlogPost::class);
        $cacheTagManager->tagWithEntityInstance($post);
        $cacheTagManager->tagWithModelClass(PageModel::class);
        $cacheTagManager->tagWithModelInstance($page);
        $cacheTagManager->tagWith([$post, $page, 'foo']);
    }

    public function testDelegatesToCacheInvalidator(): void
    {
        System::setContainer($this->getContainerWithFixtures());

        $matcher = $this->exactly(5);

        $expected = [
            [['contao.db.tl_blog_post']],
            [['contao.db.tl_blog_post.1']],
            [['contao.db.tl_page']],
            [['contao.db.tl_page.2']],
            [['contao.db.tl_blog_post.1', 'contao.db.tl_page.2', 'foo']],
        ];

        $cacheTagInvalidator = $this->createMock(CacheInvalidator::class);
        $cacheTagInvalidator
            ->expects($matcher)
            ->method('invalidateTags')
            ->with($this->callback(
                static fn (...$args) => $args === $expected[$matcher->numberOfInvocations() - 1],
            ))
        ;

        $post = (new BlogPost())->setId(1);

        $page = new PageModel();
        $page->id = 2;

        $cacheTagManager = $this->getCacheTagManager($cacheTagInvalidator);
        $cacheTagManager->invalidateTagsForEntityClass(BlogPost::class);
        $cacheTagManager->invalidateTagsForEntityInstance($post);
        $cacheTagManager->invalidateTagsForModelClass(PageModel::class);
        $cacheTagManager->invalidateTagsForModelInstance($page);
        $cacheTagManager->invalidateTagsFor([$post, $page, 'foo']);
    }

    private function getCacheTagManager(CacheInvalidator $cacheTagInvalidator, ResponseTagger|null $responseTagger = null): CacheTagManager
    {
        return new CacheTagManager(
            $this->getTestEntityManager(),
            $this->createStub(EventDispatcherInterface::class),
            $responseTagger ?? $this->createStub(ResponseTagger::class),
            $cacheTagInvalidator,
        );
    }
}
