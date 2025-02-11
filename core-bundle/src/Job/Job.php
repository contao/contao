<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Job;

use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV4;

/**
 * @experimental
 */
final class Job
{
    private Job|null $parent = null;

    /**
     * Warnings can be any string, but it's recommended to use translation identifiers
     * to have your warnings translatable.
     *
     * @var array<string>
     */
    private array $warnings = [];

    /**
     * Progress as percentage. Thus, the minimum is 0, maximum is 100.
     */
    private float $progress = 0;

    /**
     * Can be anything but must be serializable data.
     *
     * @var array<mixed>
     */
    private array $metadata = [];

    /**
     * System owner jobs can be public and thus visible by all users.
     */
    private $isPublic = false;

    public function __construct(
        private string $uuid,
        private \DateTimeInterface $createdAt,
        private Status $status,
        private Owner $owner,
    ) {
        if (!UuidV4::isValid($uuid)) {
            throw new \InvalidArgumentException(\sprintf('"%s" is not a valid UUID v4 format', $uuid));
        }
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function getOwner(): Owner
    {
        return $this->owner;
    }

    public function markPending(): self
    {
        $clone = clone $this;
        $clone->status = Status::PENDING;

        return $clone;
    }

    public function markFinished(): self
    {
        $clone = clone $this;
        $clone->status = Status::FINISHED;

        return $clone;
    }

    /**
     * @param array<string> $warnings
     */
    public function withWarnings(array $warnings): self
    {
        \assert(array_is_list($warnings), 'Warnings array must be a list.');

        foreach ($warnings as $warning) {
            if (!\is_string($warning)) {
                throw new \InvalidArgumentException(\sprintf('"%s" is not a valid string. Warnings must be strings.', $warning));
            }
        }

        $clone = clone $this;
        $clone->warnings = $warnings;

        return $clone;
    }

    /**
     * @return array<string>
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function hasWarnings(): bool
    {
        return [] !== $this->warnings;
    }

    public function withProgress(float $progress): self
    {
        \assert($progress >= 0 && $progress <= 100, 'Progress must be a valid percentage.');

        $clone = clone $this;
        $clone->progress = $progress;

        return $clone;
    }

    public function getProgress(): float
    {
        return $this->progress;
    }

    /**
     * @return array<mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * @param array<mixed> $metadata can be anything but must be serializable data
     */
    public function withMetadata(array $metadata): self
    {
        $clone = clone $this;
        $clone->metadata = $metadata;

        return $clone;
    }

    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    public function withIsPublic(bool $isPublic): self
    {
        if (Owner::SYSTEM === $this->owner->getIdentifier()) {
            throw new \InvalidArgumentException('Only system user jobs can be public or private.');
        }

        $clone = clone $this;
        $clone->isPublic = $isPublic;

        return $clone;
    }

    public function withParent(self|null $parent): self
    {
        $clone = clone $this;
        $clone->parent = $parent;

        return $clone;
    }

    public function getParent(): self|null
    {
        return $this->parent;
    }

    public static function new(Owner $owner): self
    {
        return new self(Uuid::v4()->toRfc4122(), new \DateTimeImmutable(), Status::NEW, $owner);
    }

    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'progress' => $this->progress,
            'metadata' => $this->metadata,
        ];
    }
}
