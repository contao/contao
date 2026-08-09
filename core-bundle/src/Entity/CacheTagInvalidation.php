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

use Contao\CoreBundle\Repository\CacheTagInvalidationRepository;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'tl_cache_tag_invalidation')]
#[Entity(repositoryClass: CacheTagInvalidationRepository::class)]
#[Index(name: 'identifier', columns: ['identifier'])]
#[Index(name: 'invalidate_at', columns: ['invalidateAt'])]
class CacheTagInvalidation
{
    #[Id]
    #[Column(type: 'integer', options: ['unsigned' => true])]
    #[GeneratedValue]
    protected int $id;

    #[Column(type: 'string', length: 255, nullable: true)]
    protected string|null $identifier;

    /**
     * @var list<string>
     */
    #[Column(type: 'json')]
    protected array $tags;

    #[Column(type: 'datetime_immutable')]
    protected \DateTimeImmutable $invalidateAt;

    /**
     * @param list<string> $tags
     */
    public function __construct(array $tags, \DateTimeInterface $invalidateAt, string|null $identifier = null)
    {
        $this->tags = $tags;
        $this->identifier = $identifier;
        $this->invalidateAt = \DateTimeImmutable::createFromInterface($invalidateAt)->setTimezone(new \DateTimeZone('UTC'));
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getIdentifier(): string|null
    {
        return $this->identifier;
    }

    /**
     * @return list<string>
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    public function getInvalidateAt(): \DateTimeInterface
    {
        return $this->invalidateAt;
    }
}
