<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EntityExtension;

use Contao\CoreBundle\DependencyInjection\Attribute\EntityExtension;
use Doctrine\ORM\Mapping\Column;

#[EntityExtension('Content')]
trait Content
{
    #[Column(type: 'integer', nullable: false, options: ['unsigned' => true, 'default' => '0'])]
    private ?int $pid;

    #[Column(type: 'string', length: 64, nullable: false, options: ['default' => ''])]
    private ?string $ptable;

    #[Column(type: 'integer', options: ['unsigned' => true, 'default' => '0'])]
    private ?int $tstamp;

    #[Column(type: 'string', length: 64, nullable: false, options: ['default' => 'text'])]
    private ?string $type;

    #[Column(type: 'string', length: 255, nullable: false, options: ['default' => 'a:2:{s:5:"value";s:0:"";s:4:"unit";s:2:"h2";}'])]
    private ?string $headline;

    public function getPid(): ?int
    {
        return $this->pid;
    }

    public function setPid(int $pid): self
    {
        $this->pid = $pid;

        return $this;
    }

    public function getPtable(): ?string
    {
        return $this->ptable;
    }

    public function setPtable(string $ptable): self
    {
        $this->ptable = $ptable;

        return $this;
    }

    public function getTstamp(): ?int
    {
        return $this->tstamp;
    }

    public function setTstamp(int $tstamp): self
    {
        $this->tstamp = $tstamp;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function setHeadline(string $headline): self
    {
        $this->headline = $headline;

        return $this;
    }
}
