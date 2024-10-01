<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Search\Backend;

/**
 * @experimental
 */
class Query
{
    public function __construct(
        private readonly int $perPage,
        private readonly string|null $keywords = null,
        private readonly string|null $type = null,
        private readonly string|null $tag = null,
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

    public function getType(): string|null
    {
        return $this->type;
    }

    public function getTag(): string|null
    {
        return $this->tag;
    }

    public function equals(self $otherQuery): bool
    {
        return $otherQuery->getPerPage() === $this->getPerPage()
            && $otherQuery->getKeywords() === $this->getKeywords()
            && $otherQuery->getType() === $this->getType()
            && $otherQuery->getTag() === $this->getTag();
    }
}
