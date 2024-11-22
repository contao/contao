<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Search\Backend\Event;

use Symfony\Contracts\EventDispatcher\Event;

class FormatTableDataContainerDocumentEvent extends Event
{
    private string|null $searchableContent = null;

    public function __construct(
        private readonly mixed $value,
        private readonly array $fieldConfig,
    ) {
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getFieldConfig(): array
    {
        return $this->fieldConfig;
    }

    public function setSearchableContent(string $searchableContent): self
    {
        $this->searchableContent = $searchableContent;

        return $this;
    }

    public function getSearchableContent(): string
    {
        if (null === $this->searchableContent) {
            return (string) $this->value;
        }

        return $this->searchableContent;
    }
}
