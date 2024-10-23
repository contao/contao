<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'tl_blog_post')]
#[Entity]
class BlogPost
{
    #[Id]
    #[Column(type: 'integer', options: ['unsigned' => true])]
    #[GeneratedValue]
    private int $id = -1;

    #[Column(type: 'string', options: ['default' => ''])]
    private string $text = '';

    #[ManyToOne(targetEntity: Author::class, inversedBy: 'blogPosts')]
    #[JoinColumn(name: 'author', nullable: true)]
    private Author|null $author = null;

    /**
     * @var Collection<int, Comment>
     */
    #[OneToMany(targetEntity: Comment::class, mappedBy: 'blogPost', orphanRemoval: true)]
    private Collection $comments;

    #[ManyToMany(targetEntity: 'Tag', inversedBy: 'blogPosts')]
    #[JoinTable(
        name: 'tl_blog_post_tag',
        joinColumns: [new JoinColumn(name: 'blog_post', referencedColumnName: 'id', onDelete: 'CASCADE')],
        inverseJoinColumns: [new JoinColumn(name: 'tag', referencedColumnName: 'id', onDelete: 'CASCADE')],
    )]
    private Collection $tags;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->tags = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function setText(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function getAuthor(): Author|null
    {
        return $this->author;
    }

    public function setAuthor(Author|null $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function setComments(Collection $comments): self
    {
        $this->comments = $comments;

        return $this;
    }

    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function setTags(Collection $tags): self
    {
        $this->tags = $tags;

        return $this;
    }
}
