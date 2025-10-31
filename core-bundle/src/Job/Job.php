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
    public const ERROR_REQUIRES_CLI = 'error_requires_cli';

    private self|null $parent = null;

    /**
     * Errors can be any string, but it's recommended to use translation identifiers
     * to have your error messages translatable.
     *
     * @var array<string>
     */
    private array $errors = [];

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
     */
    private array $metadata = [];

    /**
     * System owner jobs can be public and thus visible by all users.
     */
    private bool $isPublic = false;

    /**
     * @var array<Job>
     */
    private array $children = [];

    public function __construct(
        private readonly string $uuid,
        private readonly \DateTimeInterface $createdAt,
        private Status $status,
        private readonly string $type,
        private readonly Owner $owner,
    ) {
        if (!UuidV4::isValid($uuid)) {
            throw new \InvalidArgumentException(\sprintf('"%s" is not a valid UUID v4 format', $uuid));
        }

        if ('' === $type) {
            throw new \InvalidArgumentException('Job type is required');
        }
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function withProgressFromAmounts(int $total, int $amount): self
    {
        // Prevent division by 0
        if (0 === $total) {
            return $this;
        }

        $progress = 100 / $total * $amount;

        // Ensure valid percentage
        $progress = max(0, min($progress, 100));

        return $this->withProgress($progress);
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function isCompleted(): bool
    {
        return Status::completed === $this->getStatus();
    }

    public function isPending(): bool
    {
        return Status::pending === $this->getStatus();
    }

    public function isNew(): bool
    {
        return Status::new === $this->getStatus();
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getOwner(): Owner
    {
        return $this->owner;
    }

    public function markPending(): self
    {
        $clone = clone $this;
        $clone->status = Status::pending;

        return $clone;
    }

    public function markCompleted(): self
    {
        $clone = clone $this;
        $clone->status = Status::completed;
        $clone->progress = 100.0;

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
     * @param array<string> $errors
     */
    public function withErrors(array $errors): self
    {
        \assert(array_is_list($errors), 'Errors array must be a list.');

        foreach ($errors as $error) {
            if (!\is_string($error)) {
                throw new \InvalidArgumentException(\sprintf('"%s" is not a valid string. Errors must be strings.', $error));
            }
        }

        $clone = clone $this;
        $clone->errors = $errors;

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

    /**
     * @return array<string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return [] !== $this->errors;
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

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * @param array $metadata can be anything but must be serializable data
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
        if (!$this->owner->isSystem()) {
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

    public function withChild(self $child): self
    {
        $clone = clone $this;

        foreach ($clone->children as $existingChild) {
            if ($existingChild->getUuid() === $child->getUuid()) {
                return $clone;
            }
        }

        $clone->children[] = $child->withParent($this);

        return $clone;
    }

    /**
     * @return array<Job>
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    public function hasChildren(): bool
    {
        return [] !== $this->children;
    }

    public function withChildren(array $children): self
    {
        $clone = clone $this;

        foreach ($children as $child) {
            if (!$child instanceof self) {
                throw new \InvalidArgumentException('Children array must be an instance of Job.');
            }

            $clone = $clone->withChild($child);
        }

        return $clone;
    }

    public static function new(string $type, Owner $owner, \DateTimeInterface|null $createdAt = null): self
    {
        return new self(Uuid::v4()->toRfc4122(), $createdAt ?? new \DateTimeImmutable(), Status::new, $type, $owner);
    }

    public function toArray(bool $withParent = true): array
    {
        $array = [
            'uuid' => $this->getUuid(),
            'createdAt' => $this->getCreatedAt()->format('Y-m-d H:i:s'),
            'progress' => $this->getProgress(),
            'metadata' => $this->getMetadata(),
            'errors' => $this->getErrors(),
            'warnings' => $this->getWarnings(),
        ];

        if ($withParent) {
            $array['parent'] = $this->getParent()?->toArray();
        }

        $array['children'] = array_map(static fn (self $child) => $child->toArray(false), $this->getChildren());

        return $array;
    }

    public function markFailed(array $errors): self
    {
        $clone = clone $this;

        return $clone
            ->markCompleted()
            ->withErrors($errors)
        ;
    }

    public function markFailedBecauseRequiresCLI(): self
    {
        return $this->markFailed([self::ERROR_REQUIRES_CLI]);
    }
}
