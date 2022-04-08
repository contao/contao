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

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="tl_comment")
 */
class Comment
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
    private string $message = '';

    /**
     * @ORM\ManyToOne(targetEntity=Author::class, inversedBy="comments")
     * @ORM\JoinColumn(name="author", nullable=true)
     */
    private Author|null $author = null;

    /**
     * @ORM\ManyToOne(targetEntity=BlogPost::class, inversedBy="comments")
     * @ORM\JoinColumn(nullable=true, onDelete="CASCADE")
     */
    private BlogPost|null $blogPost = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getAuthor(): Author
    {
        return $this->author;
    }

    public function setAuthor(Author $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getBlogPost(): BlogPost|null
    {
        return $this->blogPost;
    }

    public function setBlogPost(BlogPost|null $blogPost): self
    {
        $this->blogPost = $blogPost;

        return $this;
    }
}
