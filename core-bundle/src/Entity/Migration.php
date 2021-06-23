<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\GeneratedValue;

/**
 * @ORM\Table(
 *     name="tl_migration",
 *     indexes={
 *         @ORM\Index(name="name", columns={"name"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="Doctrine\ORM\EntityRepository")
 */
class Migration
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer", options={"unsigned"=true})
     * @GeneratedValue
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    protected $name;

    /**
     * @var \DateTimeInterface
     *
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected $executedAt;

    public function __construct(string $name, \DateTimeInterface $executedAt = null)
    {
        $this->name = $name;
        $this->executedAt = $executedAt ?? new \DateTime();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getExecutedAt(): \DateTimeInterface
    {
        return $this->executedAt;
    }
}
