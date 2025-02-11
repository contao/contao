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
