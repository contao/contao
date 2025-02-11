<?php

namespace Contao\CoreBundle\Entity;

use Contao\CoreBundle\Job\Owner;
use Contao\CoreBundle\Job\Status;
use Contao\CoreBundle\Repository\JobRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;
use \Contao\CoreBundle\Job\Job as JobDto;

#[Table(name: 'tl_job')]
#[Entity(repositoryClass: JobRepository::class)]
#[Index(columns: ['owner'], name: 'owner_idx')]
#[Index(columns: ['status'], name: 'status_idx')]
#[Index(columns: ['public'], name: 'public_idx')]
#[Index(columns: ['created_at'], name: 'created_at_idx')]
class Job
{
    // TODO: Does that create a primary index automatically?
    #[Id]
    #[Column(type: 'string', length: 255, nullable: false)]
    private string $uuid;

    #[ManyToOne(targetEntity: Job::class, inversedBy: 'children')]
    #[JoinColumn(referencedColumnName: 'uuid', nullable: true)]
    private ?Job $parent = null;

    #[OneToMany(targetEntity: Job::class, mappedBy: 'parent')]
    private Collection $children;

    #[Column(type: 'string', length: 255, nullable: false)]
    private string $owner;

    #[Column(type: 'string', length: 255, nullable: false)]
    private string $status;

    #[Column(type: 'boolean')]
    private bool $public = false;

    #[Column(name: 'created_at', type: 'datetime', nullable: false)]
    private \DateTimeInterface $createdAt;

    #[Column(type: 'json')]
    private array $data = [];

    private function __construct()
    {
        $this->children = new ArrayCollection();
    }

    public function toDto(): JobDto
    {
        $job = new JobDto(
            $this->uuid,
            $this->createdAt,
            Status::from($this->status),
            new Owner($this->owner),
        );

        return $job
            ->withProgress($this->data['progress'] ?? 0)
            ->withWarnings($this->data['warnings'] ?? [])
            ->withMetadata($this->data['metadata'] ?? [])
            ->withIsPublic($this->public)
            ->withParent($this->parent?->toDto());
        ;
    }

    public static function fromDto(JobDto $job): self
    {
        $jobEntity = new self();
        $jobEntity->uuid = $job->getUuid();
        $jobEntity->owner = $job->getOwner()->getIdentifier();
        $jobEntity->createdAt = $job->getCreatedAt();
        $jobEntity->public = $job->isPublic();

        return $jobEntity->updateFromDto($job);
    }

    public function updateFromDto(JobDto $job): self
    {
        $data = [
            'metadata' => $job->getMetadata(),
            'progress' => $job->getProgress(),
            'warnings' => $job->getWarnings(),
        ];
        $this->status = $job->getStatus()->value;
        $this->data = $data;

        return $this;
    }

    public function getParent(): ?Job
    {
        return $this->parent;
    }

    public function setParent(?Job $parent = null): self
    {
        $this->parent = $parent;

        if ($parent) {
            $parent->children->add($this);
        } else {
            $parent->children->remove($this);
        }

        return $this;
    }

    /**
     * @return Collection<Job>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }
}
