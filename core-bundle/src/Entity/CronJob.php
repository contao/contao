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

use Contao\CoreBundle\Repository\CronJobRepository;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'tl_cron_job')]
#[Entity(repositoryClass: CronJobRepository::class)]
#[Index(columns: ['name'], name: 'name')]
class CronJob
{
    #[Id]
    #[Column(type: 'integer', options: ['unsigned' => true])]
    #[GeneratedValue]
    protected int $id;

    #[Column(type: 'string', length: 255, nullable: false)]
    protected string $name;

    #[Column(type: 'datetime', nullable: false)]
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
