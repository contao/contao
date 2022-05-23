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
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="tl_blog_post")
 */
class BlogPost
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(name="id", type="integer", options={"unsigned": true})
     */
    private int $id = -1;

    /**
     * @ORM\Column(options={"default": ""})
     */
    private string $text = '';

    /**
     * @ORM\ManyToOne(targetEntity=Author::class, inversedBy="blogPosts")
     * @ORM\JoinColumn(name="author", nullable=true)
     */
    private Author|null $author = null;

    /**
     * @ORM\OneToMany(targetEntity=Comment::class, mappedBy="blogPost", orphanRemoval=true)
     *
     * @var Collection<int, Comment>
     */
    private Collection $comments;

    /**
     * @ORM\ManyToMany(targetEntity="Tag", inversedBy="blogPosts")
     * @ORM\JoinTable(
     *     name="tl_blog_post_tag",
     *     joinColumns={@ORM\JoinColumn(name="blog_post", referencedColumnName="id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="tag", referencedColumnName="id", onDelete="CASCADE")}
     * )
     */
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
