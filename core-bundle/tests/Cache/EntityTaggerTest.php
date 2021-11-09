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

use Contao\CoreBundle\Cache\EntityTagger;
use Contao\CoreBundle\Tests\Doctrine\DoctrineTestCase;
use Contao\CoreBundle\Tests\Fixtures\Entity\Author;
use Contao\CoreBundle\Tests\Fixtures\Entity\BlogPost;
use Contao\CoreBundle\Tests\Fixtures\Entity\Comment;
use Contao\CoreBundle\Tests\Fixtures\Entity\Tag;
use Doctrine\Common\Collections\ArrayCollection;

class EntityTaggerTest extends DoctrineTestCase
{
    /**
     * @dataProvider provideArguments
     */
    public function testGetTags($argument, array $expectedTags): void
    {
        $entityTagger = $this->getEntityTagger();

        $this->assertSame($expectedTags, $entityTagger->getTags($argument));
    }

    public function provideArguments(): \Generator
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
            [$post, $post->getAuthor(), $post->getComments(), $post->getTags(), 'foo'],
            [
                'contao.db.tl_blog_post.5',
                'contao.db.tl_author.100',
                'contao.db.tl_comment.11',
                'contao.db.tl_comment.12',
                'contao.db.tl_tag.42',
                'foo',
            ],
        ];

        yield 'class-string, but not an entity' => [
            [\stdClass::class],
            ['stdClass'],
        ];

        yield 'empty and null' => [
            ['', null, [], 'foo'],
            ['foo'],
        ];
    }

    public function testGetTagForEntityClass(): void
    {
        $entityTagger = $this->getEntityTagger();

        $this->assertSame(
            'contao.db.tl_blog_post',
            $entityTagger->getTagForEntityClass(BlogPost::class)
        );
    }

    public function testThrowsIfClassIsNoEntity(): void
    {
        $entityTagger = $this->getEntityTagger();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The given class name "stdClass" is no valid entity class.');

        $entityTagger->getTagForEntityClass(\stdClass::class);
    }

    public function testGetTagForEntityInstance(): void
    {
        $entityTagger = $this->getEntityTagger();

        $post = (new BlogPost())->setId(5);

        $this->assertSame(
            'contao.db.tl_blog_post.5',
            $entityTagger->getTagForEntityInstance($post)
        );
    }

    public function testThrowsIfInstanceIsNoEntity(): void
    {
        $entityTagger = $this->getEntityTagger();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The given object of type "stdClass" is no valid entity instance.');

        $entityTagger->getTagForEntityInstance(new \stdClass());
    }

    private function getEntityTagger(): EntityTagger
    {
        return new EntityTagger($this->getTestEntityManager());
    }
}
