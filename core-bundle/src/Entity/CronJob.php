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
 *     name="tl_cron_job",
 *     indexes={
 *         @ORM\Index(name="name", columns={"name"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="Contao\CoreBundle\Repository\CronJobRepository")
 */
class CronJob
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", options={"unsigned"=true})
     * @GeneratedValue
     */
    protected int $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    protected string $name;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected \DateTimeInterface $lastRun;

    public function __construct(string $name, \DateTimeInterface $lastRun = null)
    {
        $this->name = $name;
        $this->lastRun = $lastRun ?? new \DateTime();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setLastRun(\DateTimeInterface $lastRun): self
    {
        $this->lastRun = $lastRun;

        return $this;
    }

    public function getLastRun(): \DateTimeInterface
    {
        return $this->lastRun;
    }
}
