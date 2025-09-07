<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Search\Backend;

/**
 * @experimental
 */
final class ReindexConfig
{
    private GroupedDocumentIds $limitedDocumentIds;

    private \DateTimeInterface|null $updateSince = null;

    private string|null $jobId = null;

    private bool $requireJob = false;

    public function __construct()
    {
        $this->limitedDocumentIds = new GroupedDocumentIds();
    }

    public function getUpdateSince(): \DateTimeInterface|null
    {
        return $this->updateSince;
    }

    public function limitToDocumentsNewerThan(\DateTimeInterface $dateTime): self
    {
        $clone = clone $this;
        $clone->updateSince = $dateTime;

        return $clone;
    }

    public function getLimitedDocumentIds(): GroupedDocumentIds
    {
        return $this->limitedDocumentIds;
    }

    public function limitToDocumentIds(GroupedDocumentIds $groupedDocumentIds): self
    {
        $clone = clone $this;
        $clone->limitedDocumentIds = $groupedDocumentIds;

        return $clone;
    }

    public function withJobId(string|null $jobId): self
    {
        $clone = clone $this;
        $clone->jobId = $jobId;

        return $clone;
    }

    public function withRequireJob(bool $requireJob): self
    {
        $clone = clone $this;
        $clone->requireJob = $requireJob;

        return $clone;
    }

    public function requiresJob(): bool
    {
        return $this->requireJob;
    }

    public function getJobId(): string|null
    {
        return $this->jobId;
    }

    public function equals(self $otherConfig): bool
    {
        return $this->toArray() === $otherConfig->toArray();
    }

    public function toArray(): array
    {
        return [
            'updatedSince' => $this->updateSince?->format(\DateTimeInterface::ATOM),
            'limitedDocumentIds' => $this->limitedDocumentIds->toArray(),
            'jobId' => $this->jobId,
            'requireJob' => $this->requireJob,
        ];
    }

    public static function fromArray(array $array): self
    {
        $config = new self();

        if (isset($array['updatedSince'])) {
            $config = $config->limitToDocumentsNewerThan(new \DateTimeImmutable($array['updatedSince']));
        }

        if (isset($array['jobId'])) {
            $config = $config->withJobId($array['jobId']);
        }

        if (isset($array['requireJob'])) {
            $config = $config->withRequireJob($array['requireJob']);
        }

        return $config->limitToDocumentIds(GroupedDocumentIds::fromArray($array['limitedDocumentIds']));
    }

    public function isLimitedToDocumentIdsExcludingType(string $type): bool
    {
        if ($this->getLimitedDocumentIds()->isEmpty()) {
            return false;
        }

        return !\in_array($type, $this->getLimitedDocumentIds()->getTypes(), true);
    }
}
