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
 * @ORM\Table(name="tl_tag")
 */
class Tag
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
    private string $label = '';

    /**
     * @ORM\ManyToMany(targetEntity=BlogPost::class, mappedBy="tags")
     */
    private Collection $blogPosts;

    public function __construct()
    {
        $this->blogPosts = new ArrayCollection();
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

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getBlogPosts(): Collection
    {
        return $this->blogPosts;
    }

    public function setBlogPosts(Collection $blogPosts): self
    {
        $this->blogPosts = $blogPosts;

        return $this;
    }
}
