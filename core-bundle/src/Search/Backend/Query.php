<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Search\Backend;

/**
 * @experimental
 */
class Query
{
    public function __construct(
        private readonly int $perPage = 20,
        private readonly string|null $keywords = null,
        private string|null $type = null,
        private string|null $tag = null,
    ) {
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function getKeywords(): string|null
    {
        return $this->keywords;
    }

    public function withoutTag(): self
    {
        $query = clone $this;
        $query->tag = null;

        return $query;
    }

    public function withoutType(): self
    {
        $query = clone $this;
        $query->type = null;

        return $query;
    }

    public function getType(): string|null
    {
        return $this->type;
    }

    public function getTag(): string|null
    {
        return $this->tag;
    }

    public function toUrlParams(): array
    {
        return array_filter([
            'keywords' => $this->keywords,
            'type' => $this->type,
            'tag' => $this->tag,
            'perPage' => $this->perPage,
        ]);
    }

    public function equals(self $otherQuery): bool
    {
        return $otherQuery->getPerPage() === $this->getPerPage()
            && $otherQuery->getKeywords() === $this->getKeywords()
            && $otherQuery->getType() === $this->getType()
            && $otherQuery->getTag() === $this->getTag();
    }
}
