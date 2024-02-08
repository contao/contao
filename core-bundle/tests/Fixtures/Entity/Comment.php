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

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'tl_comment')]
#[Entity]
class Comment
{
    #[Id]
    #[Column(type: 'integer', options: ['unsigned' => true])]
    #[GeneratedValue]
    private int $id = -1;

    #[Column(type: 'string', options: ['default' => ''])]
    private string $message = '';

    #[ManyToOne(targetEntity: Author::class, inversedBy: 'comments')]
    #[JoinColumn(name: 'author', nullable: true)]
    private Author|null $author = null;

    #[ManyToOne(targetEntity: BlogPost::class, inversedBy: 'comments')]
    #[JoinColumn(nullable: true, onDelete: 'CASCADE')]
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
