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
 *     name="tl_cron",
 *     indexes={
 *         @ORM\Index(name="name", columns={"name"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="Contao\CoreBundle\Repository\CronRepository")
 */
class Cron
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
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected $lastRun;

    public function __construct(string $name, \DateTime $lastRun = null)
    {
        $this->name = $name;
        $this->lastRun = $lastRun ?? new \DateTime();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setLastRun(\DateTime $lastRun): self
    {
        $this->lastRun = $lastRun;

        return $this;
    }

    public function getLastRun(): \DateTime
    {
        return $this->lastRun;
    }
}
