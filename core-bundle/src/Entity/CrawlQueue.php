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
 *     name="tl_crawl_queue",
 *     indexes={
 *         @ORM\Index(name="job_id", columns={"job_id"}),
 *         @ORM\Index(name="uri", columns={"uri"}),
 *         @ORM\Index(name="processed", columns={"processed"}),
 *     }
 * )
 * @ORM\Entity()
 */
class CrawlQueue
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer", options={"unsigned"=true})
     * @GeneratedValue
     */
    public $id;

    /**
     * @var string
     *
     * @ORM\Column(name="job_id", type="string", length=128, options={"fixed"=true})
     */
    public $jobId;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, options={"fixed"=true})
     */
    public $uri;

    /**
     * @var string
     *
     * @ORM\Column(name="found_on", type="string", nullable=true, length=255, options={"fixed"=true})
     */
    public $foundOn;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint")
     */
    public $level;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    public $processed;

    /**
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    public $tags;
}
