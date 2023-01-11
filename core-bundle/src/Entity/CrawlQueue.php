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

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'tl_crawl_queue')]
#[Entity]
#[Index(columns: ['job_id'], name: 'job_id')]
#[Index(columns: ['uri_hash'], name: 'uri_hash')]
#[Index(columns: ['processed'], name: 'processed')]
class CrawlQueue
{
    #[Id]
    #[Column(type: 'integer', options: ['unsigned' => true])]
    #[GeneratedValue]
    public int $id;

    #[Column(name: 'job_id', type: 'string', length: 128, options: ['fixed' => true])]
    public string $jobId;

    #[Column(type: 'text')]
    public string $uri;

    #[Column(name: 'uri_hash', type: 'string', length: 40, options: ['fixed' => true])]
    public string $uriHash;

    #[Column(name: 'found_on', type: 'text', nullable: true)]
    public string|null $foundOn = null;

    #[Column(type: 'smallint')]
    public int $level;

    #[Column(type: 'boolean')]
    public bool $processed;

    #[Column(type: 'text', nullable: true)]
    public string|null $tags = null;
}
